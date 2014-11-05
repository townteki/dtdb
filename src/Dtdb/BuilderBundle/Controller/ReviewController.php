<?php
namespace Dtdb\BuilderBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpFoundation\Response;
use Dtdb\BuilderBundle\Entity\Review;
use Dtdb\CardsBundle\Entity\Card;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;


class ReviewController extends Controller
{
    public function postAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        /* @var $user \Dtdb\UserBundle\Entity\User */
        $user = $this->getUser();
        if(!$user) {
            throw new UnauthorizedHttpException();
        }
        
        // a user cannot post more reviews than her reputation
        if(count($user->getReviews()) >= $user->getReputation()) {
            return new Response("Your reputation doesn't allow you to write more reviews.");
        }
        
        $card_id = filter_var($request->get('card_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $card Card */
        $card = $em->getRepository('DtdbCardsBundle:Card')->find($card_id);
        if(!$card) {
            throw new BadRequestHttpException();
        }
        
        // checking the user didn't already write a review for that card
        $review = $em->getRepository('DtdbBuilderBundle:Review')->findOneBy(array('card' => $card, 'user' => $user));
        if($review) {
            return new Response('You cannot write more than 1 review for a given card.');
        }
        
        $review_raw = trim(filter_var($request->get('review'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        
        $review_raw = preg_replace(
                '%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu',
                '[$1]($0)', $review_raw);
        
        $review_html = $this->get('texts')->markdown($review_raw);
        if(!$review_html) {
            return new Response('Your review is empty.');
        }
        
        $review = new Review();
        $review->setCard($card);
        $review->setUser($user);
        $review->setRawtext($review_raw);
        $review->setText($review_html);
        $review->setNbvotes(0);
        
        $em->persist($review);
        
        $em->flush();
        
        return new Response(json_encode(TRUE));
    }
    
    public function likeAction(Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if(!$user) {
            throw new UnauthorizedHttpException();
        }
        
        $review_id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $em->getRepository('DtdbBuilderBundle:Review')->find($review_id);
        if(!$review) {
            throw new NotFoundHttpException();
        }
        
        // a user cannot vote on her own review
        if($review->getUser()->getId() != $user->getId())
        {
            // checking if the user didn't already vote on that review
            $query = $em->getRepository('DtdbBuilderBundle:Review')
            ->createQueryBuilder('r')
            ->innerJoin('r.votes', 'u')
            ->where('r.id = :review_id')
            ->andWhere('u.id = :user_id')
            ->setParameter('review_id', $review_id)
            ->setParameter('user_id', $user->getId())
            ->getQuery();
            
            $result = $query->getResult();
            if (empty($result))
            {
                $author = $review->getUser();
                $author->setReputation($author->getReputation() + 1);
                $user->addReviewVote($review);
                $review->setNbvotes($review->getNbvotes() + 1);
                $em->flush();
            }
        }
        return new Response(count($review->getVotes()));
        
    }
    
    public function removeAction($id, Request $request)
    {
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $user = $this->getUser();
        if(!$user || !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            throw new UnauthorizedHttpException('No user or not admin');
        }
        

        $review_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $em->getRepository('DtdbBuilderBundle:Review')->find($review_id);
        if(!$review) {
            throw new NotFoundHttpException();
        }
        
        $votes = $review->getVotes();
        foreach($votes as $vote) {
            $review->removeVote($vote);
        }
        $em->remove($review);
        $em->flush();
        
        return new Response('Done');
    }
    
    public function listAction($page = 1, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        $limit = 30;
        if ($page < 1)
            $page = 1;
        $start = ($page - 1) * $limit;
        
        $pagetitle = "Card Reviews";
        
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->get('doctrine')->getConnection();
        
        /* @var $em \Doctrine\ORM\EntityManager */
        $em = $this->get('doctrine')->getManager();
        
        $reviews = $dbh->executeQuery(
                "SELECT SQL_CALC_FOUND_ROWS
                r.id,
                r.text,
                r.datecreation,
                r.nbvotes,
                r.user_id author_id,
                u.username author_name,
                u.gang author_color,
                u.reputation,
                u.donation,
                r.card_id
                from review r
                join user u on r.user_id=u.id
                where r.datecreation > DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
                order by r.datecreation desc
                limit $start, $limit")->fetchAll(\PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
        
        foreach($reviews as $i => $review) {
            $card = $em->getRepository('DtdbCardsBundle:Card')->find($review['card_id']);
            $cardinfo = $this->get('cards_data')->getCardInfo($card, false);
			$reviews[$i]['card'] = $cardinfo;
        }
        
        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page
        
        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);
        
        $route = $request->get('_route');
        
        $params = $request->query->all();
        
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
        
        return $this->render('DtdbBuilderBundle:Reviews:reviews.html.twig',
                array(
                        'pagetitle' => $pagetitle,
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'reviews' => $reviews,
                        'url' => $this->getRequest()
                        ->getRequestUri(),
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
}