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
                $card_code => 1
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
                                "id" => ""
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
            if (preg_match('/bc0f047c-01b1-427f-a439-d451eda(\d{5})/', $domElement->getAttribute('id'), $matches)) {
                $card_code = $matches[1];
            } else {
                continue;
            }
            $card = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array(
                    'code' => $card_code
            ));
            if ($card) {
                $content[$card->getCode()] = $quantity;
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

    public function meteorimportAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        // first build an array to match meteor card names with our card codes
        $glossary = array();
        $cards = $em->getRepository('DtdbCardsBundle:Card')->findAll();
        /* @var $card Card */
        foreach ($cards as $card) {
            $title = $card->getTitle();
            $replacements = array(
                    'Alix T4LB07' => 'Alix T4LBO7',
                    'Planned Assault' => 'Planned Attack',
                    'Security Testing' => 'Security Check',
                    'Mental Health Clinic' => 'Psychiatric Clinic',
                    'Shi.Kyū' => 'Shi Kyu',
                    'NeoTokyo Grid' => 'NeoTokyo City Grid',
                    'Push Your Luck' => 'Double or Nothing'
            );
            if (isset($replacements[$title])) {
                $title = $replacements[$title];
            }
            
            $pack = $card->getPack()->getName();
            if ($pack == "Core Set") {
                $pack = "Core";
            }
            
            $str = $title . " " . $pack;
            
            $str = str_replace('\'', '', $str);
            $str = strtr(utf8_decode($str), utf8_decode('ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿō'),
                    'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyyo');
            $str = strtolower($str);
            $str = preg_replace('~\W+~', '-', $str);
            $glossary[$str] = $card->getCode();
        }
        
        $url = $request->request->get('urlmeteor');
        if (! preg_match('~http://netrunner.meteor.com/users/([^/]+)~', $url, $matches)) {
            $this->get('session')
                ->getFlashBag()
                ->set('error', "Wrong URL. Please go to \"Your decks\" on Meteor Decks and copy the content of the address bar into the required field.");
            return $this->redirect($this->generateUrl('decks_list'));
        }
        $meteor_id = $matches[1];
        $meteor_json = file_get_contents("http://netrunner.meteor.com/api/decks/$meteor_id");
        $meteor_data = json_decode($meteor_json, true);
        
        // check to see if the user has enough available deck slots
        $user = $this->getUser();
        $slots_left = $user->getMaxNbDecks() - count($user->getDecks());
        $slots_required = count($meteor_data);
        if ($slots_required > $slots_left) {
            $this->get('session')
                ->getFlashBag()
                ->set('error',
                    "You don't have enough available deck slots to import the $slots_required decks from Meteor (only $slots_left slots left). You must either delete some decks here or on Meteor Decks.");
            return $this->redirect($this->generateUrl('decks_list'));
        }
        
        foreach ($meteor_data as $meteor_deck) {
            // add a tag for gang of deck
            $outfit_code = $glossary[$meteor_deck['outfit']];
            /* @var $outfit \Dtdb\CardsBundle\Entity\Card */
            $outfit = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array('code' => $outfit_code));
            if(!$outfit) continue;
            $gang_code = $outfit->getGang()->getCode();
            $tags = array($gang_code);

            $content = array(
                    $outfit_code => 1
            );
            foreach ($meteor_deck['entries'] as $entry => $qty) {
                if (! isset($glossary[$entry])) {
                    $this->get('session')
                        ->getFlashBag()
                        ->set('error', "Error importing a deck. The name \"$entry\" doesn't match any known card. Please contact the administrator.");
                    return $this->redirect($this->generateUrl('decks_list'));
                }
                $content[$glossary[$entry]] = $qty;
            }
            
            /* @var $deck Deck */
            $deck = new Deck();
            $this->get('decks')->save($this->getUser(), $deck, null, $meteor_deck['name'], "", $tags, $content);
        }
        
        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Successfully imported $slots_required decks from Meteor Decks.");
        
        return $this->redirect($this->generateUrl('decks_list'));
    
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
                "Event",
                "Hardware",
                "Resource",
                "Icebreaker",
                "Program",
                "Agenda",
                "Asset",
                "Upgrade",
                "Operation",
                "Barrier",
                "Code Gate",
                "Sentry",
                "ICE"
        );
        
        $lines[] = $deck->getOutfit()->getTitle() . " (" . $deck->getOutfit()
            ->getPack()
            ->getName() . ")";
        foreach ($types as $type) {
            if (isset($classement[$type]) && $classement[$type]['qty']) {
                $lines[] = "";
                $lines[] = $type . " (" . $classement[$type]['qty'] . ")";
                foreach ($classement[$type]['slots'] as $slot) {
                    $lines[] = $slot['qty'] . "x " . $slot['card']->getTitle() . " (" . $slot['card']->getPack()->getName() . ")";
                }
            }
        }
        $lines[] = "";
        $lines[] = $deck->getDeckSize() . " cards (must be 54)";
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
        $outfit = null;
        /** @var $slot Deckslot */
        foreach ($deck->getSlots() as $slot) {
            if ($slot->getCard()
                ->getType()
                ->getName() == "Outfit") {
                $outfit = array(
                        "index" => $slot->getCard()->getCode(),
                        "name" => $slot->getCard()->getTitle()
                );
            } else {
                $rd[] = array(
                        "index" => $slot->getCard()->getCode(),
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
        return $this->octgnexport("$name.o8d", $outfit, $rd, $deck->getDescription());
    
    }

    public function octgnexport ($filename, $outfit, $rd, $description)
    {

        $content = $this->renderView('DtdbBuilderBundle::octgn.xml.twig',
                array(
                        "outfit" => $outfit,
                        "rd" => $rd,
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

        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks())
            return new Response('You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.');
        
        $is_copy = (boolean) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $content = (array) json_decode($request->get('content'));
        if (! count($content))
            return new Response('Cannot import empty deck');
        
        if ($is_copy && $id) {
            $id = null;
        }
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        if ($id) {
            $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($id);
            if ($user->getId() != $deck->getUser()->getId())
                throw new UnauthorizedHttpException("You don't have access to this deck.");
            foreach ($deck->getSlots() as $slot) {
                $deck->removeSlot($slot);
                $em->remove($slot);
            }
        } else {
            $deck = new Deck();
        }
        
        $this->get('decks')->save($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $content);

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
                d.tags
				from deck d
				where d.id=?
				", array(
                $deck_id
        ))->fetchAll();
        
        $deck = $rows[0];
        
        $rows = $dbh->executeQuery("SELECT
				c.code,
				s.quantity
				from deckslot s
				join card c on s.card_id=c.id
				where s.deck_id=?", array(
                $deck_id
        ))->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $cards[$row['code']] = $row['quantity'];
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
				s.quantity
				from deckslot s
				join card c on s.card_id=c.id
				where s.deck_id=?", array(
                $deck_id
        ))->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $cards[$row['code']] = $row['quantity'];
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
            $content[$slot->getCard()->getCode()] = $slot->getQuantity();
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
            $content[$slot->getCard()->getCode()] = $slot->getQuantity();
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
                    if($packname == 'Core Set') $packname = 'Core';
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
                 $this->get('decks')->save($this->getUser(), $deck, null, $name, '', '', $parse['content']);
            }
        }
        $zip->close();

        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Decks imported.");
        
        return $this->redirect($this->generateUrl('decks_list'));
    }
}

