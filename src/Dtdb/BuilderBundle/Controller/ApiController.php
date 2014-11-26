<?php

namespace Dtdb\BuilderBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Dtdb\BuilderBundle\Entity\Deck;
use Dtdb\BuilderBundle\Entity\Deckslot;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Michelf\Markdown;
use Dtdb\BuilderBundle\Entity\Decklist;
use Dtdb\BuilderBundle\Entity\Decklistslot;
use Symfony\Component\HttpFoundation\Request;

class ApiController extends Controller
{

    public function decklistAction ($decklist_id)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        $response->headers->add(array(
                'Access-Control-Allow-Origin' => '*'
        ));
        
        $jsonp = $this->getRequest()->query->get('jsonp');
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.creation,
				d.description,
				u.username
				from decklist d
				join user u on d.user_id=u.id
				where d.id=?
				", array(
                        $decklist_id
                ))->fetchAll();
        
        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }
        
        $decklist = $rows[0];
        $decklist['id'] = intval($decklist['id']);
        
        $lastModified = new \DateTime($decklist['ts']);
        $response->setLastModified($lastModified);
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }
        unset($decklist['ts']);
        
        $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
                $decklist_id
        ))->fetchAll();
        
        $decklist['cards'] = array();
        foreach ($cards as $card) {
            $decklist['cards'][$card['card_code']] = intval($card['qty']);
        }
        
        $content = json_encode($decklist);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        
        $response->setContent($content);
        return $response;
    
    }

    public function decklistsAction ($date)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        $response->headers->add(array(
                'Access-Control-Allow-Origin' => '*'
        ));
        
        $jsonp = $this->getRequest()->query->get('jsonp');
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        $dbh = $this->get('doctrine')->getConnection();
        $decklists = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.creation,
				d.description,
				u.username
				from decklist d
				join user u on d.user_id=u.id
				where substring(d.creation,1,10)=?
				", array(
                        $date
                ))->fetchAll();
        
        $lastTS = null;
        foreach ($decklists as $i => $decklist) {
            $lastTS = max($lastTS, $decklist['ts']);
            unset($decklists[$i]['ts']);
        }
        $response->setLastModified(new \DateTime($lastTS));
        if ($response->isNotModified($this->getRequest())) {
            return $response;
        }
        
        foreach ($decklists as $i => $decklist) {
            $decklists[$i]['id'] = intval($decklist['id']);
            
            $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
                    $decklists[$i]['id']
            ))->fetchAll();
            
            $decklists[$i]['cards'] = array();
            foreach ($cards as $card) {
                $decklists[$i]['cards'][$card['card_code']] = intval($card['qty']);
            }
        }
        
        $content = json_encode($decklists);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        
        $response->setContent($content);
        return $response;
    
    }

    public function decksAction ()
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');
        
        $locale = $this->getRequest()->query->get('_locale');
        if (isset($locale))
            $this->getRequest()->setLocale($locale);
        
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        
        if (! $user) {
            throw new UnauthorizedHttpException();
        }
        
        $response->setContent(json_encode($this->get('decks')->getByUser($user)));
        return $response;
    }
 
    public function saveDeckAction($deck_id)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');

        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks())
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.')));
            return $response;
        }
        
        $request = $this->getRequest();
        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $content = json_decode($request->get('content'), true);
        if (! count($content))
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'Cannot import empty deck')));
            return $response;
        }
        
        $em = $this->getDoctrine()->getManager();
        
        if ($deck_id) {
            $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
            if ($user->getId() != $deck->getUser()->getId())
            {
                $response->setContent(json_encode(array('success' => false, 'message' => 'Wrong user')));
                return $response;
            }
            foreach ($deck->getSlots() as $slot) {
                $deck->removeSlot($slot);
                $em->remove($slot);
            }
        } else {
            $deck = new Deck();
        }
        
        // $content is formatted as {card_code,qty}, expected {card_code=>qty}
        $slots = array();
        foreach($content as $arr) {
            $slots[$arr['card_code']] = intval($arr['qty']);
        }
        
        $deck_id = $this->get('decks')->saveDeck($this->getUser(), $deck, $decklist_id, $name, $description, $tags, $slots, $deck_id ? $deck : null);
        
        if(isset($deck_id))
        {
            $response->setContent(json_encode(array('success' => true, 'message' => $this->get('decks')->getById($deck_id))));
            return $response;
        }
        else
        {
            $response->setContent(json_encode(array('success' => false, 'message' => 'Unknown error')));
            return $response;
        }
    }
    
    public function publishAction($deck_id, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $deck \Dtdb\BuilderBundle\Entity\Deck */
        $deck = $this->getDoctrine()
        ->getRepository('DtdbBuilderBundle:Deck')
        ->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            $response->setContent(json_encode(array('success' => false, 'message' => "You don't have access to this deck.")));
            return $response;
        }
        
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            $response->setContent(json_encode(array('success' => false, 'message' => $judge->problem($analyse))));
            return $response;
        }
        
        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()
        ->getRepository('DtdbBuilderBundle:Decklist')
        ->findBy(array(
                'signature' => $new_signature
        ));
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                $response->setContent(json_encode(array('success' => false, 'message' => "That decklist already exists.")));
                return $response;
            }
        }
        
        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = $deck->getName();
        }
        
        $rawdescription = filter_var($request->request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        if (empty($rawdescription)) {
            $rawdescription = $deck->getDescription();
        }
        $description = Markdown::defaultTransform($rawdescription);
        
        $decklist = new Decklist();
        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setUser($this->getUser());
        $decklist->setCreation(new \DateTime());
        $decklist->setTs(new \DateTime());
        $decklist->setSignature($new_signature);
        $decklist->setOutfit($deck->getOutfit());
        $decklist->setGang($deck->getOutfit()
                ->getGang());
        $decklist->setLastPack($deck->getLastPack());
        $decklist->setNbvotes(0);
        $decklist->setNbfavorites(0);
        $decklist->setNbcomments(0);
        foreach ($deck->getSlots() as $slot) {
            $card = $slot->getCard();
            $decklistslot = new Decklistslot();
            $decklistslot->setQuantity($slot->getQuantity());
            $decklistslot->setCard($card);
            $decklistslot->setDecklist($decklist);
            $decklist->getSlots()->add($decklistslot);
        }
        if (count($deck->getChildren())) {
            $decklist->setPrecedent($deck->getChildren()[0]);
        } else
        if ($deck->getParent()) {
            $decklist->setPrecedent($deck->getParent());
        }
        $decklist->setParent($deck);
        
        $em->persist($decklist);
        $em->flush();
        
        $response->setContent(json_encode(array('success' => true, 'message' => array("id" => $decklist->getId(), "url" => $this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist->getId(),
                'decklist_name' => $decklist->getPrettyName()
        ))))));
        return $response;
        
    }
    
}
