<?php

namespace Dtdb\UserBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class DefaultController extends Controller
{
    public function profileAction()
    {
    	$user = $this->getUser();
    	
    	$gangs = $this->get('doctrine')->getRepository('DtdbCardsBundle:Gang')->findAll();
    	foreach($gangs as $i => $gang) {
    		$gangs[$i]->localizedName = $gang->getName($this->getRequest()->getLocale());
    	}
    	
    	return $this->render('DtdbUserBundle:Default:profile.html.twig', array(
    			'user'=> $user, 'gangs' => $gangs));
    }
    
    public function saveProfileAction()
    {
    	/* @var $user \Dtdb\UserBundle\Entity\User */
    	$user = $this->getUser();
    	$request = $this->getRequest();
    	
    	$resume = filter_var($request->get('resume'), FILTER_SANITIZE_STRING);
    	$gang_code = filter_var($request->get('user_gang_code'), FILTER_SANITIZE_STRING);
    	$notifAuthor = $request->get('notif_author') ? TRUE : FALSE;
    	$notifCommenter = $request->get('notif_commenter') ? TRUE : FALSE;
    	$notifMention = $request->get('notif_mention') ? TRUE : FALSE;
    	 
    	$user->setGang($gang_code);
    	$user->setResume($resume);
    	$user->setNotifAuthor($notifAuthor);
    	$user->setNotifCommenter($notifCommenter);
    	$user->setNotifMention($notifMention);
    	
    	$this->get('doctrine')->getManager()->flush();
    	
        $this->get('session')
            ->getFlashBag()
            ->set('notice', "Successfully saved your profile.");
		
    	return $this->redirect($this->generateUrl('user_profile'));
    }
    
    public function infoAction(Request $request)
    {
        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if(isset($locale)) $request->setLocale($locale);
        $locale = $request->getLocale();
        
        $decklist_id = $request->query->get('decklist_id');
        $card_id = $request->query->get('card_id');
        
        $content = null;
        
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        if($user)
        {
            $user_id = $user->getId();
            
            $public_profile_url = $this->get('router')->generate('user_profile_view', array(
                    '_locale' => $this->getRequest()->getLocale(),
                    'user_id' => $user_id,
                    'user_name' => urlencode($user->getUsername())
            ));
            $content = array(
                    'public_profile_url' => $public_profile_url,
                    'id' => $user_id,
                    'name' => $user->getUsername(),
                    'gang' => $user->getGang(),
                    'locale' => $locale
            );
            
            if(isset($decklist_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->get('doctrine')->getManager();
                /* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
                $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
                
				if ($decklist) {
    				$decklist_id = $decklist->getId();
				    
    				$dbh = $this->get('doctrine')->getConnection();
    				
    			    $content['is_liked'] = (boolean) $dbh->executeQuery("SELECT
        				count(*)
        				from decklist d
        				join vote v on v.decklist_id=d.id
        				where v.user_id=?
        				and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];
                    
    			    $content['is_favorite'] = (boolean) $dbh->executeQuery("SELECT
        				count(*)
        				from decklist d
        				join favorite f on f.decklist_id=d.id
        				where f.user_id=?
        				and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];
                    
    			    $content['is_author'] = ($user_id == $decklist->getUser()->getId());
    
    			    $content['can_delete'] = ($decklist->getNbcomments() == 0) && ($decklist->getNbfavorites() == 0) && ($decklist->getNbvotes() == 0);
				}
            }

            if(isset($card_id)) {
                /* @var $em \Doctrine\ORM\EntityManager */
                $em = $this->get('doctrine')->getManager();
                /* @var $card \Dtdb\CardsBundle\Entity\Card */
                $card = $em->getRepository('DtdbCardsBundle:Card')->find($card_id);
            
                if($card) {
                    $reviews = $card->getReviews();
                    /* @var $review \Dtdb\BuilderBundle\Entity\Review */
                    foreach($reviews as $review) {
                        if($review->getUser()->getId() === $user->getId()) {
                            $content['review_id'] = $review->getId();
                            $content['review_text'] = $review->getRawtext();
                        }
                    }
                }
            }
        }
        $content = json_encode($content);

        $response = new Response();
        $response->setPrivate();
        if(isset($jsonp))
        {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else
        {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        
        return $response;
        
    }
}
