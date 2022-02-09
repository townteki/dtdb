<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Review;
use App\Entity\User;
use App\Services\CardsData;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    /**
     * @Route("/review/post", name="card_review_post", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Texts $texts
     * @param Request $request
     * @return Response
     */
    public function postAction(EntityManagerInterface $entityManager, Texts $texts, Request $request)
    {
        /* @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('Access denied. Please log in first.');
        }

        // a user cannot post more reviews than her reputation
        if (count($user->getReviews()) >= $user->getReputation()) {
            return new Response("Your reputation doesn't allow you to write more reviews.");
        }

        $card_id = filter_var($request->get('card_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var Card $card */
        $card = $entityManager->getRepository(Card::class)->find($card_id);
        if (!$card) {
            throw new BadRequestHttpException();
        }

        // checking the user didn't already write a review for that card
        /* @var Review $review */
        $review = $entityManager->getRepository(Review::class)->findOneBy(['card' => $card, 'user' => $user]);
        if ($review) {
            return new Response('You cannot write more than 1 review for a given card.');
        }

        $review_raw = trim(filter_var($request->get('review'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $review_raw = preg_replace(
            '%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu',
            '[$1]($0)',
            $review_raw
        );
        $review_html = $texts->markdown($review_raw);
        if (!$review_html) {
            return new Response('Your review is empty.');
        }

        $review = new Review();
        $review->setCard($card);
        $review->setUser($user);
        $review->setRawtext($review_raw);
        $review->setText($review_html);
        $review->setNbvotes(0);

        $entityManager->persist($review);
        $entityManager->flush();

        return new Response(json_encode(true));
    }

    /**
     * @Route("/review/edit", name="card_review_edit", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Texts $texts
     * @param Request $request
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, Texts $texts, Request $request)
    {
        /* @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('Access denied. Please log in first.');
        }

        $review_id = filter_var($request->get('review_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var $review Review */
        $review = $entityManager->getRepository(Review::class)->find($review_id);
        if (!$review) {
            throw new BadRequestHttpException();
        }

        if ($review->getUser()->getId() !== $user->getId()) {
            throw new UnauthorizedHttpException("Access denied. You cannot edit other user's reviews.");
        }

        $review_raw = trim(filter_var($request->get('review'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $review_raw = preg_replace(
            '%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?)(?:[^\s]*)?%iu',
            '[$1]($0)',
            $review_raw
        );

        $review_html = $texts->markdown($review_raw);
        if (!$review_html) {
            return new Response('Your review is empty.');
        }

        $review->setRawtext($review_raw);
        $review->setText($review_html);

        $entityManager->flush();

        return new Response(json_encode(true));
    }

    /**
     * @Route("/review/like", name="card_review_like", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function likeAction(EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('Access denied. Please log in first.');
        }

        $review_id = filter_var($request->request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $repo = $entityManager->getRepository(Review::class);
        /* @var Review $review */
        $review = $repo->find($review_id);
        if (!$review) {
            throw new NotFoundHttpException();
        }

        // a user cannot vote on her own review
        if ($review->getUser()->getId() != $user->getId()) {
            // checking if the user didn't already vote on that review
            $query = $repo
            ->createQueryBuilder('r')
            ->innerJoin('r.votes', 'u')
            ->where('r.id = :review_id')
            ->andWhere('u.id = :user_id')
            ->setParameter('review_id', $review_id)
            ->setParameter('user_id', $user->getId())
            ->getQuery();

            $result = $query->getResult();
            if (empty($result)) {
                $author = $review->getUser();
                $author->setReputation($author->getReputation() + 1);
                $user->addReviewVote($review);
                $review->setNbvotes($review->getNbvotes() + 1);
                $entityManager->flush();
            }
        }
        return new Response(count($review->getVotes()));
    }

    /**
     * @Route("/review/remove/{id}", name="card_review_remove", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param $id
     * @param Request $request
     * @return Response
     */
    public function removeAction(EntityManagerInterface $entityManager, $id, Request $request)
    {
        $user = $this->getUser();
        if (!$user || !in_array('ROLE_SUPER_ADMIN', $user->getRoles())) {
            throw new UnauthorizedHttpException('No user or not admin');
        }

        $review_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var Review $review  */
        $review = $entityManager->getRepository(Review::class)->find($review_id);
        if (!$review) {
            throw new NotFoundHttpException();
        }

        $votes = $review->getVotes();
        foreach ($votes as $vote) {
            $review->removeVote($vote);
        }
        $entityManager->remove($review);
        $entityManager->flush();

        return new Response('Done');
    }

    /**
     * @Route(
     *     "/{_locale}/reviews/{page}",
     *     name="card_reviews_list",
     *     methods={"GET"},
     *     defaults={"page"=1},
     *     locale="en",
     *     requirements={
     *         "page"="\d+",
     *         "user_id"="\d+",
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param CardsData $cardsData
     * @param $shortCache
     * @param Request $request
     * @param $page
     * @return Response
     */
    public function listAction(
        EntityManagerInterface $entityManager,
        CardsData $cardsData,
        $shortCache,
        Request $request,
        $page = 1
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        $limit = 30;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;
        $pagetitle = "Card Reviews";
        $dbh = $entityManager->getConnection();
        $reviews = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
            r.id,
            r.text,
            r.datecreation,
            r.nbvotes,
            r.user_id AS author_id,
            u.username AS author_name,
            u.gang AS author_color,
            u.reputation,
            u.donation,
            r.card_id
            FROM review r
            JOIN user u ON r.user_id = u.id
            ORDER BY r.datecreation DESC
            LIMIT $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        $repo = $entityManager->getRepository(Card::class);
        foreach ($reviews as $i => $review) {
            /* @var Card $card */
            $card = $repo->find($review['card_id']);
            $cardinfo = $cardsData->getCardInfo($card, false);
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

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, $params + ["page" => $page]),
                "current" => $page == $currpage
            ];
        }

        return $this->render(
            'Reviews/reviews.html.twig',
            [
                'pagetitle' => $pagetitle,
                'locales' => $this->renderView('Default/langs.html.twig'),
                'reviews' => $reviews,
                'url' => $request->getRequestUri(),
                'route' => $route,
                'pages' => $pages,
                'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, $params + ["page" => $prevpage]),
                'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + ["page" => $nextpage]),
            ],
            $response
        );
    }
}
