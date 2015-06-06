<?php
namespace Dtdb\BuilderBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Dtdb\BuilderBundle\Entity\Deck;
use Dtdb\BuilderBundle\Entity\Deckslot;
use Dtdb\CardsBundle\Entity\Card;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Dtdb\BuilderBundle\Entity\Deckchange;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class BuilderController extends Controller
{

    public function buildformAction (Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $type = $em->getRepository('DtdbCardsBundle:Type')->findOneBy(array(
                "name" => "Outfit"
        ));
        
        $identities = $em->getRepository('DtdbCardsBundle:Card')->findBy(array(
                "type" => $type
        ), array(
                "gang" => "ASC",
                "title" => "ASC"
        ));
        
        return $this->render('DtdbBuilderBundle:Builder:initbuild.html.twig',
                array(
                        'pagetitle' => "New deck",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        "identities" => array_filter($identities,
                                function  ($iden)
                                {
                                    return $iden->getPack()
                                        ->getCode() != "alt";
                                })
                ), $response);
    
    }

    public function initbuildAction ($card_code)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $card Card */
        $card = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array(
                "code" => $card_code
        ));
        if (! $card)
            return new Response('card not found.');
        
        $arr = array(
                $card_code => array(
                        'quantity' => 1,
                        'start' => 0
                )
        );
        return $this->render('DtdbBuilderBundle:Builder:deck.html.twig',
                array(
                        'pagetitle' => "Deckbuilder",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'deck' => array(
                                "slots" => $arr,
                                "name" => "New Deck",
                                "description" => "",
                                "tags" => $card->getGang()->getCode(),
                                "id" => "",
                                "history" => array(),
                                "unsaved" => 0,
                        ),
                        "published_decklists" => array()
                ), $response);
    
    }

    public function importAction ()
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        return $this->render('DtdbBuilderBundle:Builder:directimport.html.twig',
                array(
                        'pagetitle' => "Import a deck",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig')
                ), $response);
    
    }

    public function fileimportAction (Request $request)
    {

        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('upfile');
        if (! isset($uploadedFile))
            return new Response('No file');
        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();
        
        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            
            // check to see if the mime-type starts with 'text'
            $is_text = substr(finfo_file($finfo, $filename), 0, 4) == 'text' || substr(finfo_file($finfo, $filename), 0, 15) == "application/xml";
            if (! $is_text)
                return new Response('Bad file');
        }
        
        if ($filetype == "octgn" || ($filetype == "auto" && $origext == "o8d")) {
            $parse = $this->parseOctgnImport(file_get_contents($filename));
        } else {
            $parse = $this->parseTextImport(file_get_contents($filename));
        }
        return $this->forward('DtdbBuilderBundle:Builder:save',
                array(
                        'name' => $origname,
                        'content' => json_encode($parse['content']),
                        'description' => $parse['description']
                ));
    
    }

    public function parseTextImport ($text)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $content = array();
        $lines = explode("\n", $text);
        $outfit = null;
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d)x?([\pLl\pLu\pN\-\.\'\!\: ]+)/u', $line, $matches)) {
                $quantity = intval($matches[1]);
                $name = trim($matches[2]);
            } else
                if (preg_match('/^([^\(]+).*x(\d)/', $line, $matches)) {
                    $quantity = intval($matches[2]);
                    $name = trim($matches[1]);
                } else
                    if (empty($outfit) && preg_match('/([^\(]+):([^\(]+)/', $line, $matches)) {
                        $quantity = 1;
                        $name = trim($matches[1] . ":" . $matches[2]);
                        $outfit = $name;
                    } else {
                        continue;
                    }
            $card = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array(
                    'title' => $name
            ));
            if ($card) {
                $content[$card->getCode()] = $quantity;
            }
        }
        return array(
                "content" => $content,
                "description" => ""
        );
    
    }

    public function parseOctgnImport ($octgn)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $content = array();
        
        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);
        $cardcrawler = $crawler->filter('deck > section > card');
        
        $content = array();
        foreach ($cardcrawler as $domElement) {
            $quantity = intval($domElement->getAttribute('qty'));
            $card = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array(
                    'octgnid' => $domElement->getAttribute('id')
            ));
            if ($card) {
                $content[$card->getCode()] = (isset($content[$card->getCode()]) ? $content[$card->getCode()] : 0) + $quantity;
            }
        }
        
        $desccrawler = $crawler->filter('deck > notes');
        $description = array();
        foreach ($desccrawler as $domElement) {
            $description[] = $domElement->nodeValue;
        }
        return array(
                "content" => $content,
                "description" => implode("\n", $description)
        );
    
    }

    public function textexportAction ($deck_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $deck \Dtdb\BuilderBundle\Entity\Deck */
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
        if (! $this->getUser() || $this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");
            
            /* @var $judge \Dtdb\SocialBundle\Services\Judge */
        $judge = $this->get('judge');
        $classement = $judge->classe($deck->getCards(), $deck->getOutfit());
        
        $lines = array();
        $types = array(
                "Dude",
                "Deed",
                "Goods",
                "Spell",
                "Action"
        );

        $lines[] = $deck->getOutfit()->getTitle() . " (" . $deck->getOutfit()
            ->getPack()
            ->getName() . ")";
        foreach ($types as $type) {
            if (isset($classement[$type]) && $classement[$type]['qty']) {
                $lines[] = "";
                $lines[] = $type . " (" . $classement[$type]['qty'] . ")";
                foreach ($classement[$type]['slots'] as $slot) {
                	$start ="";
                	for($loop=$slot['start']; $loop>0; $loop--) $start.="*";
                    $lines[] = $slot['qty'] . "x " . $slot['card']->getTitle() . $start . " (" . $slot['card']->getPack()->getName() . ")";
                }
            }
        }
        $lines[] = "";
        $lines[] = "Cards up to " . $deck->getLastPack()->getName();
        $content = implode("\r\n", $lines);
        
        $name = mb_strtolower($deck->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        
        $response = new Response();
        
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $name . ".txt");
        
        $response->setContent($content);
        return $response;
    
    }

    public function octgnexportAction ($deck_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $deck \Dtdb\BuilderBundle\Entity\Deck */
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
        if (! $this->getUser() || $this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        
        $rd = array();
        $start = array();
        $outfit = null;
        /** @var $slot Deckslot */
        foreach ($deck->getSlots() as $slot) {
            if ($slot->getCard()
                ->getType()
                ->getName() == "Outfit") {
                $outfit = array(
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle()
                );
            } else if($slot->getStart()) {
                $start[] = array(
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle(),
                        "qty" => $slot->getStart()
                );
                if($slot->getQuantity() > $slot->getStart()) {
                    $rd[] = array(
                            "id" => $slot->getCard()->getOctgnid(),
                            "name" => $slot->getCard()->getTitle(),
                            "qty" => $slot->getQuantity() - $slot->getStart()
                    );
                }
            } else {
                $rd[] = array(
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle(),
                        "qty" => $slot->getQuantity()
                );
            }
        }
        $name = mb_strtolower($deck->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        if (empty($outfit)) {
            return new Response('no outfit found');
        }
        return $this->octgnexport("$name.o8d", $outfit, $start, $rd, $deck->getDescription());
    
    }

    public function octgnexport ($filename, $outfit, $start, $rd, $description)
    {

        $content = $this->renderView('DtdbBuilderBundle::octgn.xml.twig',
                array(
                        "outfit" => $outfit,
                        "start" => $start,
                        "deck" => $rd,
                        "description" => strip_tags($description)
                ));
        
        $response = new Response();
        
        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);
        
        $response->setContent($content);
        return $response;
    
    }

    public function saveAction (Request $request)
    {

        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks())
            return new Response('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        
        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if($id) {
            $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new UnauthorizedHttpException("You don't have access to this deck.");
            }
            $source_deck = $deck;
        }
        
        $cancel_edits = (boolean) filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if($cancel_edits) {
        	if($deck){
            	$this->get('decks')->revertDeck($deck);
            	return $this->redirect($this->generateUrl('decks_list'));
        	} else {
            	return $this->redirect($this->generateUrl('decks_list'));        		
        	}
        }
        
        $is_copy = (boolean) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if($is_copy || !$id) {
            $deck = new Deck();
        }

        $content = json_decode($request->get('content'), TRUE);
        if (! count($content)) {
            return new Response('Cannot import empty deck');
        }
        
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        
        $this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $content, $source_deck ? $source_deck : null);

        return $this->redirect($this->generateUrl('decks_list'));
    
    }

    public function deleteAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
        if (! $deck)
            return $this->redirect($this->generateUrl('decks_list'));
        if ($this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        
        foreach ($deck->getChildren() as $decklist) {
            $decklist->setParent(null);
        }
        $em->remove($deck);
        $em->flush();
        
        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Deck deleted.");
        
        return $this->redirect($this->generateUrl('decks_list'));
    
    }

    public function deleteListAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $list_id = explode('-', $request->get('ids'));

        foreach($list_id as $id)
        {
            /* @var $deck Deck */
            $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($id);
            if(!$deck) continue;
            if ($this->getUser()->getId() != $deck->getUser()->getId()) continue;
            
            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent(null);
            }
            $em->remove($deck);
        }
        $em->flush();
        
        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Decks deleted.");
        
        return $this->redirect($this->generateUrl('decks_list'));
    }

    public function editAction ($deck_id)
    {

        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery("SELECT
				d.id,
				d.name,
				d.description,
                DATE_FORMAT(d.datecreation, '%Y-%m-%dT%TZ') datecreation,
                DATE_FORMAT(d.dateupdate, '%Y-%m-%dT%TZ') dateupdate,
                (select count(*) from deckchange c where c.deck_id=d.id and c.saved=0) unsaved,
                d.tags
				from deck d
				where d.id=?
				", array(
                $deck_id
        ))->fetchAll();
        
        $deck = $rows[0];
        
        $rows = $dbh->executeQuery("SELECT
				c.code,
				s.quantity,
                s.start
				from deckslot s
				join card c on s.card_id=c.id
				where s.deck_id=?", array(
                $deck_id
        ))->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $cards[$row['code']] = array(
                    "quantity" => intval($row['quantity']),
                    "start" => intval($row['start'])
            );
        }
        
        $snapshots = array();
        $changes = $dbh->executeQuery("SELECT
				DATE_FORMAT(c.datecreation, '%Y-%m-%dT%TZ') datecreation,
				c.variation,
                c.saved
				from deckchange c
				where c.deck_id=? and c.saved=1
                order by datecreation desc", array($deck_id))->fetchAll();
        // recreating the versions with the variation info, starting from $preversion
        $preversion = $cards;
        foreach ($changes as $change) {
            $change['variation'] = $variation = json_decode($change['variation'], TRUE);
            $change['saved'] = (boolean) $change['saved'];
            // add preversion with variation that lead to it
            $change['content'] = $preversion;
            array_unshift($snapshots, $change);
            // applying variation to create 'next' (older) preversion
            foreach($variation[0] as $code => $qty) {
                $preversion[$code]["quantity"] = $preversion[$code]["quantity"] - $qty;
                if($preversion[$code]["quantity"] == 0) unset($preversion[$code]);
            }
            foreach($variation[1] as $code => $qty) {
                if(!isset($preversion[$code])) $preversion[$code] = array("quantity" => 0, "start" => 0);
                $preversion[$code]["quantity"] = $preversion[$code]["quantity"] + $qty;
            }
            ksort($preversion);
        }
        // add last know version with empty diff
        $change['content'] = $preversion;
        $change['datecreation'] = $deck['datecreation'];
        $change['saved'] = TRUE;
        $change['variation'] = null;
        array_unshift($snapshots, $change);
        $changes = $dbh->executeQuery("SELECT
				DATE_FORMAT(c.datecreation, '%Y-%m-%dT%TZ') datecreation,
				c.variation,
                c.saved
				from deckchange c
				where c.deck_id=? and c.saved=0
                order by datecreation asc", array($deck_id))->fetchAll();
        // recreating the snapshots with the variation info, starting from $postversion
        $postversion = $cards;
        foreach ($changes as $change) {
            $change['variation'] = $variation = json_decode($change['variation'], TRUE);
            $change['saved'] = (boolean) $change['saved'];
            // applying variation to postversion
            foreach($variation[0] as $code => $qty) {
                if(!isset($postversion[$code])) $postversion[$code] = array("quantity" => 0, "start" => 0);
                $postversion[$code]["quantity"] = $postversion[$code]["quantity"] + $qty;
            }
            foreach($variation[1] as $code => $qty) {
                $postversion[$code]["quantity"] = $postversion[$code]["quantity"] - $qty;
                if($postversion[$code]["quantity"] == 0) unset($postversion[$code]);
            }
            ksort($postversion);
            // add postversion with variation that lead to it
            $change['content'] = $postversion;
            array_push($snapshots, $change);
        }
        // current deck is newest snapshot
        $deck['slots'] = $postversion;
        // hsitory is deck contents without 'start' key
        $deck['history'] = array_map(function ($snapshot) {
            $snapshot['content'] = array_map(function ($value) {
                return $value['quantity'];
            }, $snapshot['content']);
            return $snapshot;
        }, $snapshots);
        
        $published_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.prettyname,
					d.nbvotes,
					d.nbfavorites,
					d.nbcomments
					from decklist d
					where d.parent_deck_id=?
					order by d.creation asc", array(
                        $deck_id
                ))->fetchAll();
        
        return $this->render('DtdbBuilderBundle:Builder:deck.html.twig',
                array(
                        'pagetitle' => "Deckbuilder",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'deck' => $deck,
                        'published_decklists' => $published_decklists
                ));
    
    }

    public function viewAction ($deck_id)
    {

        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery("SELECT
				d.id,
				d.name,
				d.description,
                d.problem,
				c.code outfit_code,
				f.code gang_code
                from deck d
				join card c on d.outfit_id=c.id
				join gang f on c.gang_id=f.id
                where d.id=?
				", array(
                $deck_id
        ))->fetchAll();
        
        $deck = $rows[0];
        
        $rows = $dbh->executeQuery("SELECT
				c.code,
				s.quantity,
                s.start
				from deckslot s
				join card c on s.card_id=c.id
				where s.deck_id=?", array(
                $deck_id
        ))->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $cards[$row['code']] = array(
                    "quantity" => intval($row['quantity']),
                    "start" => intval($row['start'])
            );
        }
        $deck['slots'] = $cards;
        
        $published_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.prettyname,
					d.nbvotes,
					d.nbfavorites,
					d.nbcomments
					from decklist d
					where d.parent_deck_id=?
					order by d.creation asc", array(
                        $deck_id
                ))->fetchAll();

		$problem = $deck['problem'];
		$deck['message'] = isset($problem) ? $this->get('judge')->problem($problem) : '';
		
        return $this->render('DtdbBuilderBundle:Builder:deckview.html.twig',
                array(
                        'pagetitle' => "Deckbuilder",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'deck' => $deck,
                        'published_decklists' => $published_decklists
                ));
    
    }

    public function listAction ()
    {
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        
        return $this->render('DtdbBuilderBundle:Builder:decks.html.twig',
                array(
                        'pagetitle' => "My Decks",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'decks' => $this->get('decks')
                            ->getByUser($user),
                        'nbmax' => $user->getMaxNbDecks()
                ));
    
    }

    public function copyAction ($decklist_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        
        $content = array();
        foreach ($decklist->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = array(
                    "quantity" => $slot->getQuantity(),
                    "start" => $slot->getStart()
            );
        }
        return $this->forward('DtdbBuilderBundle:Builder:save',
                array(
                        'name' => $decklist->getName(),
                        'content' => json_encode($content),
                        'decklist_id' => $decklist_id
                ));
    
    }

    public function duplicateAction ($deck_id)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
    
        /* @var $deck \Dtdb\BuilderBundle\Entity\Deck */
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
    
        $content = array();
        foreach ($deck->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = array(
                    "quantity" => $slot->getQuantity(),
                    "start" => $slot->getStart()
            );
        }
        return $this->forward('DtdbBuilderBundle:Builder:save',
                array(
                        'name' => $deck->getName().' (copy)',
                        'content' => json_encode($content),
                        'deck_id' => $deck->getParent() ? $deck->getParent()->getId() : null
                ));
    
    }
    
    public function downloadallAction()
    {
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $decks = $this->get('decks')->getByUser($user);

        $file = tempnam("tmp", "zip");
        $zip = new \ZipArchive();
        $res = $zip->open($file, \ZipArchive::OVERWRITE);
        if ($res === TRUE)
        {
            foreach($decks as $deck)
            {
                $content = array();
                foreach($deck['cards'] as $slot)
                {
                    $card = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array('code' => $slot['card_code']));
                    if(!$card) continue;
                    $cardtitle = $card->getTitle();
                    $packname = $card->getPack()->getName();
                    $qty = $slot['qty'];
                    $content[] = "$cardtitle ($packname) x$qty";
                }
                $filename = str_replace('/', ' ', $deck['name']).'.txt';
                $zip->addFromString($filename, implode("\r\n", $content));
            }
            $zip->close();
        }
        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set('Content-Length', filesize($file));
        $response->headers->set('Content-Disposition', 'attachment; filename="dtdb.zip"');
        $response->setContent(file_get_contents($file));
        unlink($file);
        return $response;
    }
    
    public function uploadallAction(Request $request)
    {
        // time-consuming task
        ini_set('max_execution_time', 300);
        
        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('uparchive');
        if (! isset($uploadedFile))
            return new Response('No file');
        
        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();
    
        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
    
            // check to see if the mime-type is 'zip'
            if(substr(finfo_file($finfo, $filename), 0, 15) !== 'application/zip')
                return new Response('Bad file');
        }
        
        $zip = new \ZipArchive;
        $res = $zip->open($filename);
        if ($res === TRUE) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                 $name = $zip->getNameIndex($i);
                 $parse = $this->parseTextImport($zip->getFromIndex($i));
                 
                 $deck = new Deck();
                 $this->get('decks')->saveDeck($this->getUser(), $deck, null, $name, '', '', $parse['content']);
            }
        }
        $zip->close();

        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Decks imported.");
        
        return $this->redirect($this->generateUrl('decks_list'));
    }
    public function autosaveAction($deck_id, Request $request)
    {
        $user = $this->getUser();
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
        if(!$deck) {
            throw new BadRequestHttpException("Cannot find deck ".$deck_id);
        }
        if ($user->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }
        $diff = json_decode($request->get('diff'), TRUE);
        if (count($diff) != 2) {
            throw new BadRequestHttpException("Wrong content ".$diff);
        }
        if(count($diff[0]) || count($diff[1])) {
            $change = new Deckchange();
            $change->setDeck($deck);
            $change->setVariation(json_encode($diff));
            $change->setSaved(FALSE);
            $em->persist($change);
            $em->flush();
        }
        return new Response($change->getDatecreation()->format('c'));
    }
}

