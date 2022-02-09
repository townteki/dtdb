<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Entity\Gang;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class UserController extends AbstractController
{
    /**
     * @Route(
     *     "/{_locale}/user/profile",
     *     name="user_profile",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function profileAction(EntityManagerInterface $entityManager)
    {
        $user = $this->getUser();
        $gangs = $entityManager->getRepository(Gang::class)->findAll();
        // @todo localized name is a myth, this is an english-only site. remove/replace [ST 2022/05/01]
        foreach ($gangs as $i => $gang) {
            $gangs[$i]->localizedName = $gang->getName();
        }
        return $this->render('User/profile.html.twig', ['user' => $user, 'gangs' => $gangs]);
    }

    /**
     * @Route(
     *     "/{_locale}/user/profile_save",
     *     name="user_profile_save",
     *     locale="en",
     *     methods={"POST"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     * @param Request $request
     * @return RedirectResponse
     */
    public function saveProfileAction(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        Request $request
    ) {
        $user = $this->getUser();
        $resume = filter_var($request->get('resume'), FILTER_SANITIZE_STRING);
        $gang_code = filter_var($request->get('user_gang_code'), FILTER_SANITIZE_STRING);
        $notifAuthor = $request->get('notif_author') ? true : false;
        $notifCommenter = $request->get('notif_commenter') ? true : false;
        $notifMention = $request->get('notif_mention') ? true : false;

        $user->setGang($gang_code);
        $user->setResume($resume);
        $user->setNotifAuthor($notifAuthor);
        $user->setNotifCommenter($notifCommenter);
        $user->setNotifMention($notifMention);

        $entityManager->flush();
        $session
            ->getFlashBag()
            ->set('notice', "Successfully saved your profile.");
        return $this->redirect($this->generateUrl('user_profile'));
    }

    /**
     * @Route("/user/info", name="user_info", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param Request $request
     * @return Response
     */
    public function infoAction(EntityManagerInterface $entityManager, RouterInterface $router, Request $request)
    {
        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }
        $locale = $request->getLocale();

        $decklist_id = $request->query->get('decklist_id');
        $card_id = $request->query->get('card_id');

        $content = null;

        $user = $this->getUser();
        if ($user) {
            $user_id = $user->getId();

            $public_profile_url = $router->generate('user_profile_view', [
                '_locale' => $request->getLocale(),
                'user_id' => $user_id,
                'user_name' => urlencode($user->getUsername()),
            ]);
            $content = [
                'public_profile_url' => $public_profile_url,
                'id' => $user_id,
                'name' => $user->getUsername(),
                'gang' => $user->getGang(),
                'locale' => $locale,
            ];

            if (isset($decklist_id)) {
                $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);

                if ($decklist) {
                    $decklist_id = $decklist->getId();

                    $dbh = $entityManager->getConnection();

                    $content['is_liked'] = (bool) $dbh->executeQuery("SELECT
                        COUNT(*)
                        FROM decklist d
                        JOIN vote v ON v.decklist_id = d.id
                        WHERE v.user_id = ?
                        AND d.id = ?", [$user_id, $decklist_id])->fetch(PDO::FETCH_NUM)[0];

                    $content['is_favorite'] = (bool) $dbh->executeQuery("SELECT
                        COUNT(*)
                        FROM decklist d
                        JOIN favorite f ON f.decklist_id = d.id
                        WHERE f.user_id = ?
                        AND d.id = ?", [$user_id, $decklist_id])->fetch(PDO::FETCH_NUM)[0];

                    $content['is_author'] = ($user_id == $decklist->getUser()->getId());

                    $content['can_delete'] = (
                        $decklist->getNbcomments() == 0)
                        && ($decklist->getNbfavorites() == 0)
                        && ($decklist->getNbvotes() == 0
                    );
                }
            }

            if (isset($card_id)) {
                $card = $entityManager->getRepository(Card::class)->find($card_id);

                if ($card) {
                    $reviews = $card->getReviews();
                    foreach ($reviews as $review) {
                        if ($review->getUser()->getId() === $user->getId()) {
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
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        return $response;
    }
}
