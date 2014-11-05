<?php
namespace Dtdb\BuilderBundle\Controller;
use \DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Dtdb\BuilderBundle\Entity\Deck;
use Dtdb\BuilderBundle\Entity\Deckslot;
use Dtdb\BuilderBundle\Entity\Decklist;
use Dtdb\BuilderBundle\Entity\Decklistslot;
use Dtdb\BuilderBundle\Entity\Comment;
use Dtdb\UserBundle\Entity\User;
use \Michelf\Markdown;
use Symfony\Component\HttpFoundation\Request;

class SocialController extends Controller
{
    /*
	 * checks to see if a deck can be published in its current saved state
	 */
    public function publishAction ($deck_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $deck = $em->getRepository('DtdbBuilderBundle:Deck')->find($deck_id);
        
        if ($this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());
        
        if (is_string($analyse))
            throw new AccessDeniedHttpException($judge->problem($analyse));
        
        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $this->getDoctrine()
            ->getRepository('DtdbBuilderBundle:Decklist')
            ->findBy(array(
                'signature' => $new_signature
        ));
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                return new Response($this->generateUrl('decklist_detail', array(
                        'decklist_id' => $decklist->getId(),
                        'decklist_name' => $decklist->getPrettyName()
                )));
            }
        }
        
        return new Response('');
    
    }
    
    /*
	 * creates a new decklist from a deck (publish action)
	 */
    public function newAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $deck_id = filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $deck \Dtdb\BuilderBundle\Entity\Deck */
        $deck = $this->getDoctrine()
            ->getRepository('DtdbBuilderBundle:Deck')
            ->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId())
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        
        $judge = $this->get('judge');
        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            throw new AccessDeniedHttpException($judge->problem($analyse));
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
                throw new AccessDeniedHttpException('That decklist already exists.');
            }
        }
        
        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name))
            $name = "Untitled";
        $rawdescription = filter_var($request->request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
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
            $decklistslot->setStart($slot->getStart());
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
        
        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist->getId(),
                'decklist_name' => $decklist->getPrettyName()
        )));
    
    }

    private function searchForm(Request $request)
    {
        $cards_code = $request->query->get('cards');
        $gang_code = filter_var($request->query->get('gang'), FILTER_SANITIZE_STRING);
        $lastpack_code = filter_var($request->query->get('lastpack'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_title = filter_var($request->query->get('title'), FILTER_SANITIZE_STRING);
        $sort = $request->query->get('sort');
        
        $dbh = $this->get('doctrine')->getConnection();
        
        $packs = $dbh->executeQuery(
                "SELECT
				p.name,
				p.code,
                '' as selected
				from pack p
				where p.released is not null
				order by p.released desc")
        				->fetchAll();
        
        foreach($packs as $i => $pack) {
            $packs[$i]['selected'] = ($pack['code'] == $lastpack_code) ? ' selected="selected"' : '';
        }
        $params = array(
                'packs' => $packs,
                'author' => $author_name,
                'title' => $decklist_title
        );
        $params['sort_'.$sort] = ' selected="selected"';
        $params['gang_'.substr($gang_code, 0, 1)] = ' selected="selected"';

        if (! empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery(
                    "SELECT
    				c.title" . ($request
            				        ->getLocale() == "en" ? '' : '_' . $request
            				        ->getLocale()) . " title,
    				c.code,
                    f.code gang_code
    				from card c
                    join gang f on f.id=c.gang_id
                    where c.code in (?)
    				order by c.code desc", array($cards_code), array(\Doctrine\DBAL\Connection::PARAM_INT_ARRAY))
            				->fetchAll();

            $params['cards'] = '';
            foreach($cards as $card) {
                $params['cards'] .= $this->renderView('DtdbBuilderBundle:Search:card.html.twig', $card);
            }
                        
        }
        
        return $this->renderView('DtdbBuilderBundle:Search:form.html.twig', $params);
    }
    
    /*
	 * displays the lists of decklists
	 */
    public function listAction ($type, $code = null, $page = 1, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        $limit = 30;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;
        
        $pagetitle = "Decklists";
        $header = '';

        switch ($type) {
            case 'find':
                $result = $this->get('decklists')->find($start, $limit, $request);
                $pagetitle = "Decklist search results";
                $header = $this->searchForm($request);
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = array('decklists' => array(), 'count' => 0);
                } else {
                    $result = $this->get('decklists')->favorites($user->getId(), $start, $limit);
                }
                $pagetitle = "Favorite Decklists";
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = array('decklists' => array(), 'count' => 0);
                } else {
                    $result = $this->get('decklists')->by_author($user->getId(), $start, $limit);
                }
                $pagetitle = "My Decklists";
                break;
            case 'recent':
                $result = $this->get('decklists')->recent($start, $limit);
                $pagetitle = "Recent Decklists";
                break;
            case 'halloffame':
                $result = $this->get('decklists')->halloffame($start, $limit);
                $pagetitle = "Hall of Fame";
                break;
            case 'hottopics':
                $result = $this->get('decklists')->hottopics($start, $limit);
                $pagetitle = "Hot Topics";
                break;
            case 'popular':
            default:
                $result = $this->get('decklists')->popular($start, $limit);
                $pagetitle = "Popular Decklists";
                break;
        }
        
        $decklists = $result['decklists'];
        $maxcount = $result['count'];
        
        $dbh = $this->get('doctrine')->getConnection();
        $gangs = $dbh->executeQuery(
                "SELECT
				f.name" . ($request
                    ->getLocale() == "en" ? '' : '_' . $request
                    ->getLocale()) . " name,
				f.code
				from gang f
				order by f.name asc")
            ->fetchAll();
        
        $packs = $dbh->executeQuery(
                "SELECT
				p.name" . ($request
                    ->getLocale() == "en" ? '' : '_' . $request
                    ->getLocale()) . " name,
				p.code
				from pack p
				where p.released is not null
				order by p.released desc
				limit 0,5")
            ->fetchAll();
        
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        
        $route = $request->get('_route');
        
        $params = $request->query->all();
        $params['type'] = $type;
        $params['code'] = $code;
        
        $pages = array();
        for ($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                    "numero" => $page,
                    "url" => $this->generateUrl($route, $params + array(
                            "page" => $page
                    )),
                    "current" => $page == $currpage
            );
        }
        
        return $this->render('DtdbBuilderBundle:Decklist:decklists.html.twig',
                array(
                        'pagetitle' => $pagetitle,
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'decklists' => $decklists,
                        'packs' => $packs,
                        'gangs' => $gangs,
                        'url' => $request
                            ->getRequestUri(),
                        'header' => $header,
                        'route' => $route,
                        'pages' => $pages,
                        'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, $params + array(
                                "page" => $prevpage
                        )),
                        'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + array(
                                "page" => $nextpage
                        ))
                ), $response);
    
    }
    
    /*
	 * displays the content of a decklist along with comments, siblings, similar, etc.
	 */
    public function viewAction ($decklist_id, $decklist_name, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        $dbh = $this->get('doctrine')->getConnection();
        $rows = $dbh->executeQuery(
                "SELECT
				d.id,
				d.ts,
				d.name,
				d.prettyname,
				d.creation,
				d.rawdescription,
				d.description,
				d.precedent_decklist_id precedent,
				u.id user_id,
				u.username,
				u.gang usercolor,
				u.reputation,
				u.donation,
				c.code outfit_code,
				f.code gang_code,
				d.nbvotes,
				d.nbfavorites,
				d.nbcomments
				from decklist d
				join user u on d.user_id=u.id
				join card c on d.outfit_id=c.id
				join gang f on d.gang_id=f.id
				where d.id=?
				", array(
                        $decklist_id
                ))->fetchAll();
        
        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }
        
        $decklist = $rows[0];
        
        $comments = $dbh->executeQuery(
                "SELECT
				c.id,
				c.creation,
				c.user_id,
				u.username author,
				u.gang authorcolor,
                u.donation,
				c.text
				from comment c
				join user u on c.user_id=u.id
				where c.decklist_id=?
				order by creation asc", array(
                        $decklist_id
                ))->fetchAll();
        
		$commenters = array_values(array_unique(array_merge(array($decklist['username']), array_map(function ($item) { return $item['author']; }, $comments))));
				
        $cards = $dbh->executeQuery("SELECT
				c.code card_code,
				s.quantity qty,
                s.start start
				from decklistslot s
				join card c on s.card_id=c.id
				where s.decklist_id=?
				order by c.code asc", array(
                $decklist_id
        ))->fetchAll();
        
        $decklist['comments'] = $comments;
        $decklist['cards'] = $cards;
        
        $similar_decklists = array(); // $this->findSimilarDecklists($decklist_id,
                                      // 5);
        
        $precedent_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.prettyname,
					d.nbvotes,
					d.nbfavorites,
					d.nbcomments
					from decklist d
					where d.id=?
					order by d.creation asc", array(
                        $decklist['precedent']
                ))->fetchAll();
        
        $successor_decklists = $dbh->executeQuery(
                "SELECT
					d.id,
					d.name,
					d.prettyname,
					d.nbvotes,
					d.nbfavorites,
					d.nbcomments
					from decklist d
					where d.precedent_decklist_id=?
					order by d.creation asc", array(
                        $decklist_id
                ))->fetchAll();
        
        return $this->render('DtdbBuilderBundle:Decklist:decklist.html.twig',
                array(
                        'pagetitle' => $decklist['name'],
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'decklist' => $decklist,
                        'commenters' => $commenters,
                        'similar' => $similar_decklists,
                        'precedent_decklists' => $precedent_decklists,
                        'successor_decklists' => $successor_decklists
                ), $response);
    
    }
    
    /*
	 * adds a decklist to a user's list of favorites
	 */
    public function favoriteAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if(!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }
        
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        
        /* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        if (! $decklist)
            throw new NotFoundHttpException('Wrong id');
        
        $author = $decklist->getUser();
        
        $dbh = $this->get('doctrine')->getConnection();
        $is_favorite = $dbh->executeQuery("SELECT
				count(*)
				from decklist d
				join favorite f on f.decklist_id=d.id
				where f.user_id=?
				and d.id=?", array(
                $user->getId(),
                $decklist_id
        ))
            ->fetch(\PDO::FETCH_NUM)[0];
        
        if ($is_favorite) {
            $decklist->setNbfavorites($decklist->getNbfavorites() - 1);
            $user->removeFavorite($decklist);
            if ($author->getId() != $user->getId())
                $author->setReputation($author->getReputation() - 5);
        } else {
            $decklist->setNbfavorites($decklist->getNbfavorites() + 1);
            $user->addFavorite($decklist);
            $decklist->setTs(new \DateTime());
            if ($author->getId() != $user->getId())
                $author->setReputation($author->getReputation() + 5);
        }
        $this->get('doctrine')
            ->getManager()
            ->flush();
        
        return new Response(count($decklist->getFavorites()));
    
    }
    
    /*
	 * records a user's comment
	 */
    public function commentAction (Request $request)
    {
        /* @var $user User */
        $user = $this->getUser();
        if(!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }
        
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $decklist = $this->getDoctrine()
            ->getRepository('DtdbBuilderBundle:Decklist')
            ->find($decklist_id);
        
        $comment_text = trim(filter_var($request->get('comment'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        if ($decklist && ! empty($comment_text)) {
            $comment_text = preg_replace(
                    '%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu',
                    '[$1]($0)', $comment_text);
            
            $mentionned_usernames = array();
            if(preg_match_all('/`@([\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }
            
            $comment_html = Markdown::defaultTransform($comment_text);
            
            $now = new DateTime();
            
            $comment = new Comment();
            $comment->setText($comment_html);
            $comment->setCreation($now);
            $comment->setAuthor($user);
            $comment->setDecklist($decklist);
            
            $this->get('doctrine')
                ->getManager()
                ->persist($comment);
            $decklist->setTs($now);
            $decklist->setNbcomments($decklist->getNbcomments() + 1);

            $this->get('doctrine')
            ->getManager()
            ->flush();
            
            // send emails
            $spool = array();
            if($decklist->getUser()->getNotifAuthor()) {
                if(!isset($spool[$decklist->getUser()->getEmail()])) {
                    $spool[$decklist->getUser()->getEmail()] = 'DtdbBuilderBundle:Emails:newcomment_author.html.twig';
                }
            }
            foreach($decklist->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getAuthor();
                if($commenter && $commenter->getNotifCommenter()) {
                    if(!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'DtdbBuilderBundle:Emails:newcomment_commenter.html.twig';
                    }
                }
            }
            foreach($mentionned_usernames as $mentionned_username) {
                /* @var $mentionned_user User */
                $mentionned_user = $this->getDoctrine()->getRepository('DtdbUserBundle:User')->findOneBy(array('username' => $mentionned_username));
                if($mentionned_user && $mentionned_user->getNotifMention()) {
                    if(!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'DtdbBuilderBundle:Emails:newcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);
            
            $email_data = array(
                'username' => $user->getUsername(),
                'decklist_name' => $decklist->getName(),
                'url' => $this->generateUrl('decklist_detail', array('decklist_id' => $decklist->getId(), 'decklist_name' => $decklist->getPrettyname()), TRUE) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile', array(), TRUE)
            );
            foreach($spool as $email => $view) {
                $message = \Swift_Message::newInstance()
                ->setSubject("[DoomtownDB] New comment")
                ->setFrom(array("no_reply@dtdb.co" => $user->getUsername()))
                ->setTo($email)
                ->setBody($this->renderView($view, $email_data), 'text/html');
                $this->get('mailer')->send($message);
            }
            
        }
        
        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist_id,
                'decklist_name' => $decklist->getPrettyName()
        )));
    
    }
    
    /*
	 * records a user's vote
	 */
    public function voteAction (Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if(!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }
                
        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        $query = $em->getRepository('DtdbBuilderBundle:Decklist')
            ->createQueryBuilder('d')
            ->innerJoin('d.votes', 'u')
            ->where('d.id = :decklist_id')
            ->andWhere('u.id = :user_id')
            ->setParameter('decklist_id', $decklist_id)
            ->setParameter('user_id', $user->getId())
            ->getQuery();
        
        $result = $query->getResult();
        if (empty($result)) {
            $user->addVote($decklist);
            $author = $decklist->getUser();
            $author->setReputation($author->getReputation() + 1);
            $decklist->setTs(new \DateTime());
            $decklist->setNbvotes($decklist->getNbvotes() + 1);
            $this->get('doctrine')
            ->getManager()
            ->flush();
        }
        
        return new Response(count($decklist->getVotes()));
    
    }
    
    /*
	 * (unused) returns an ordered list of decklists similar to the one given
	 */
    public function findSimilarDecklists ($decklist_id, $number)
    {

        $dbh = $this->get('doctrine')->getConnection();
        
        $list = $dbh->executeQuery(
                "SELECT
    			l.id,
    			(
    				SELECT COUNT(s.id)
    				FROM decklistslot s
    				WHERE (
    					s.decklist_id=l.id
    					AND s.card_id NOT IN (
    						SELECT t.card_id
    						FROM decklistslot t
    						WHERE t.decklist_id=?
    					)
    				)
    				OR
    				(
    					s.decklist_id=?
    					AND s.card_id NOT IN (
    						SELECT t.card_id
    						FROM decklistslot t
    						WHERE t.decklist_id=l.id
    					)
			    	)
    			) difference
     			FROM decklist l
    			WHERE l.id!=?
    			ORDER BY difference ASC
    			LIMIT 0,$number", array(
                        $decklist_id,
                        $decklist_id,
                        $decklist_id
                ))->fetchAll();
        
        $arr = array();
        foreach ($list as $item) {
            
            $dbh = $this->get('doctrine')->getConnection();
            $rows = $dbh->executeQuery("SELECT
					d.id,
					d.name,
					d.prettyname,
					d.nbvotes,
					d.nbfavorites,
					d.nbcomments
					from decklist d
					where d.id=?
					", array(
                    $item["id"]
            ))->fetchAll();
            
            $decklist = $rows[0];
            $arr[] = $decklist;
        }
        return $arr;
    
    }
    
    /*
	 * returns a text file with the content of a decklist
	 */
    public function textexportAction ($decklist_id, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        if (! $decklist)
            throw new NotFoundHttpException();
            
            /* @var $judge \Dtdb\SocialBundle\Services\Judge */
        $judge = $this->get('judge');
        $classement = $judge->classe($decklist->getCards(), $decklist->getOutfit());
        
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
        
        $lines[] = $decklist->getOutfit()->getTitle() . " (" . $decklist->getOutfit()
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
        $content = implode("\r\n", $lines);
        
        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        
        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $name . ".txt");
        
        $response->setContent($content);
        return $response;
    
    }
    
    /*
	 * returns a octgn file with the content of a decklist
	 */
    public function octgnexportAction ($decklist_id, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        if (! $decklist)
            throw new NotFoundHttpException();
        
        $rd = array();
        $start = array();
        $outfit = null;
        /** @var $slot Decklistslot */
        foreach ($decklist->getSlots() as $slot) {
            if ($slot->getCard()->getType()->getName() == "Outfit") {
                $outfit = array(
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle()
                );
            } else if($slot->getStart()) {
                $start[] = array(
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle()
                );
                if($slot->getQuantity() > 1) {
                    $rd[] = array(
                            "id" => $slot->getCard()->getOctgnid(),
                            "name" => $slot->getCard()->getTitle(),
                            "qty" => $slot->getQuantity()-1
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
        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        if (empty($outfit)) {
            return new Response('no outfit found');
        }
        return $this->octgnexport("$name.o8d", $outfit, $rd, $start, $decklist->getRawdescription(), $response);
    
    }
    
    /*
	 * does the "downloadable file" part of the export
	 */
    public function octgnexport ($filename, $outfit, $rd, $start, $description, $response)
    {

        $content = $this->renderView('DtdbBuilderBundle::octgn.xml.twig', array(
                "outfit" => $outfit,
                "start" => $start,
                "deck" => $rd,
                "description" => strip_tags($description)
        ));
        
        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);
        
        $response->setContent($content);
        return $response;
    
    }
    
    /*
	 * edits name and description of a decklist by its publisher
	 */
    public function editAction ($decklist_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if (! $user)
            throw new UnauthorizedHttpException("You must be logged in for this operation.");
        
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId())
            throw new UnauthorizedHttpException("You don't have access to this decklist.");
        
        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name))
            $name = "Untitled";
        $rawdescription = trim(filter_var($request->request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $description = Markdown::defaultTransform($rawdescription);
        
        $derived_from = $request->request->get('derived');
        if(preg_match('/^(\d+)$/', $derived_from, $matches)) {
            
        } else if(preg_match('/decklist\/(\d+)\//', $derived_from, $matches)) {
            $derived_from = $matches[1];
        } else {
            $derived_from = null;
        }
        
        if(!$derived_from) {
            $precedent_decklist = null;
        }
        else {
            /* @var $precedent_decklist Decklist */
            $precedent_decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($derived_from);
            if(!$precedent_decklist || $precedent_decklist->getCreation() > $decklist->getCreation()) {
                $precedent_decklist = $decklist->getPrecedent();
            }
        }
        
        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setPrecedent($precedent_decklist);
        $decklist->setTs(new \DateTime());
        $em->flush();
        
        return $this->redirect($this->generateUrl('decklist_detail', array(
                'decklist_id' => $decklist_id,
                'decklist_name' => $decklist->getPrettyName()
        )));
    
    }
    
    /*
	 * deletes a decklist if it has no comment, no vote, no favorite
	*/
    public function deleteAction ($decklist_id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if (! $user)
            throw new UnauthorizedHttpException("You must be logged in for this operation.");
        
        $decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId())
            throw new UnauthorizedHttpException("You don't have access to this decklist.");
        
        if ($decklist->getNbvotes() || $decklist->getNbfavorites() || $decklist->getNbcomments())
            throw new UnauthorizedHttpException("Cannot delete this decklist.");
        
        $precedent = $decklist->getPrecedent();
        
        $children_decks = $decklist->getChildren();
        /* @var $children_deck Deck */
        foreach ($children_decks as $children_deck) {
            $children_deck->setParent($precedent);
        }
        
        $successor_decklists = $decklist->getSuccessors();
        /* @var $successor_decklist Decklist */
        foreach ($successor_decklists as $successor_decklist) {
            $successor_decklist->setPrecedent($precedent);
        }
        
        $em->remove($decklist);
        $em->flush();
        
        return $this->redirect($this->generateUrl('decklists_list', array(
                'type' => 'mine'
        )));
    
    }
    
    /*
	 * displays details about a user and the list of decklists he published
	 */
    public function profileAction ($user_id, $user_name, $page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $em->getRepository('DtdbUserBundle:User')->find($user_id);
        if (! $user)
            throw new NotFoundHttpException("No such user.");
        
        $limit = 100;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;
        
        $result = $this->get('decklists')->by_author($user_id, $start, $limit);
        
        $decklists = $result['decklists'];
        $maxcount = $result['count'];
        $count = count($decklists);
        
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        
        $route = $request->get('_route');
        
        $pages = array();
        for ($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                    "numero" => $page,
                    "url" => $this->generateUrl($route, array(
                            "user_id" => $user_id,
                            "user_name" => $user_name,
                            "page" => $page
                    )),
                    "current" => $page == $currpage
            );
        }
        
        return $this->render('DtdbBuilderBundle:Default:profile.html.twig',
                array(
                        'pagetitle' => $user->getUsername(),
                        'user' => $user,
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'decklists' => $decklists,
                        'url' => $request
                            ->getRequestUri(),
                        'route' => $route,
                        'pages' => $pages,
                        'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, array(
                                "user_id" => $user_id,
                                "user_name" => $user_name,
                                "page" => $prevpage
                        )),
                        'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, array(
                                "user_id" => $user_id,
                                "user_name" => $user_name,
                                "page" => $nextpage
                        ))
                ), $response);
    
    }

    public function usercommentsAction ($page, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        
        $limit = 100;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;
        
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->get('doctrine')->getConnection();
        
        $comments = $dbh->executeQuery(
                "SELECT SQL_CALC_FOUND_ROWS
				c.id,
				c.text,
				c.creation,
				d.id decklist_id,
				d.name decklist_name,
				d.prettyname decklist_prettyname
				from comment c
				join decklist d on c.decklist_id=d.id
				where c.user_id=?
				order by creation desc
				limit $start, $limit", array(
                        $user->getId()
                ))
            ->fetchAll(\PDO::FETCH_ASSOC);
        
        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
        
        $count = count($comments);
        
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        
        $route = $request->get('_route');
        
        $pages = array();
        for ($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                    "numero" => $page,
                    "url" => $this->generateUrl($route, array(
                            "page" => $page
                    )),
                    "current" => $page == $currpage
            );
        }
        
        return $this->render('DtdbBuilderBundle:Default:usercomments.html.twig',
                array(
                        'user' => $user,
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'comments' => $comments,
                        'url' => $request
                            ->getRequestUri(),
                        'route' => $route,
                        'pages' => $pages,
                        'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, array(
                                "page" => $prevpage
                        )),
                        'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, array(
                                "page" => $nextpage
                        ))
                ), $response);
    
    }

    public function commentsAction ($page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        $limit = 100;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;
        
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->get('doctrine')->getConnection();
        
        $comments = $dbh->executeQuery(
                "SELECT SQL_CALC_FOUND_ROWS
				c.id,
				c.text,
				c.creation,
				d.id decklist_id,
				d.name decklist_name,
				d.prettyname decklist_prettyname,
				u.id user_id,
				u.username author
				from comment c
				join decklist d on c.decklist_id=d.id
				join user u on c.user_id=u.id
				order by creation desc
				limit $start, $limit", array())->fetchAll(\PDO::FETCH_ASSOC);
        
        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
        
        $count = count($comments);
        
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        
        $route = $request->get('_route');
        
        $pages = array();
        for ($page = 1; $page <= $nbpages; $page ++) {
            $pages[] = array(
                    "numero" => $page,
                    "url" => $this->generateUrl($route, array(
                            "page" => $page
                    )),
                    "current" => $page == $currpage
            );
        }
        
        return $this->render('DtdbBuilderBundle:Default:allcomments.html.twig',
                array(
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'comments' => $comments,
                        'url' => $request
                            ->getRequestUri(),
                        'route' => $route,
                        'pages' => $pages,
                        'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, array(
                                "page" => $prevpage
                        )),
                        'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, array(
                                "page" => $nextpage
                        ))
                ), $response);
    
    }

    public function searchAction (Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('long_cache'));
        
        $dbh = $this->get('doctrine')->getConnection();
        $gangs = $dbh->executeQuery(
                "SELECT
				f.name" . ($request
                    ->getLocale() == "en" ? '' : '_' . $request
                    ->getLocale()) . " name,
				f.code
				from gang f
				order by f.name asc")
            ->fetchAll();
        
        $packs = $dbh->executeQuery(
                "SELECT
				p.name,
                p.code,
                '' as selected
				from pack p
				where p.released is not null
				order by p.released desc")
            ->fetchAll();
        
        return $this->render('DtdbBuilderBundle:Search:search.html.twig',
                array(
                        'pagetitle' => 'Decklist Search',
                        'url' => $request
                            ->getRequestUri(),
                        'gangs' => $gangs,
                        'form' => $this->renderView('DtdbBuilderBundle:Search:form.html.twig',
                            array(
                                    'packs' => $packs,
                                    'author' => '',
                                    'title' => ''
                            )
                        ),
                ), $response);
    
    }

    public function donatorsAction (Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->get('doctrine')->getConnection();
        
        $users = $dbh->executeQuery("SELECT * FROM user WHERE donation>0 ORDER BY donation DESC, username", array())->fetchAll(\PDO::FETCH_ASSOC);
        
        return $this->render('DtdbBuilderBundle:Default:donators.html.twig',
                array(
                        'pagetitle' => 'The Gracious Donators',
                        'donators' => $users
                ), $response);
    }

}
