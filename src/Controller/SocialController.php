<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Decklistslot;
use App\Entity\Tournament;
use App\Entity\User;
use App\Services\Decklists;
use App\Services\Judge;
use DateTime;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Michelf\Markdown;
use PDO;
use Swift_Mailer;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SocialController extends AbstractController
{
    /**
     * @Route(
     *     "/deck/can_publish/{deck_id}",
     *     name="deck_publish",
     *     methods={"GET"},
     *     requirements={
     *         "deck_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Judge $judge
     * @param $deck_id
     * @param Request $request
     * @return Response
     */
    public function publishAction(EntityManagerInterface $entityManager, Judge $judge, $deck_id, Request $request)
    {
        /* @var Deck $deck */
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);

        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        $analyse = $judge->analyse($deck->getCards());

        if (is_string($analyse)) {
            throw new AccessDeniedHttpException($judge->problem($analyse));
        }

        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $entityManager->getRepository(Decklist::class)
            ->findBy(['signature' => $new_signature]);
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                return new Response($this->generateUrl('decklist_detail', [
                    'decklist_id' => $decklist->getId(),
                    'decklist_name' => $decklist->getPrettyName()
                ]));
            }
        }

        return new Response('');
    }

    /**
     * @Route(
     *     "/{_locale}/deck/publish",
     *     name="decklist_new",
     *     locale="en",
     *     methods={"POST"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Judge $judge
     * @param Request $request
     * @return RedirectResponse
     */
    public function newAction(EntityManagerInterface $entityManager, Judge $judge, Request $request)
    {
        $deck_id = filter_var($request->request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var Deck $deck */
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            throw new AccessDeniedHttpException($judge->problem($analyse));
        }

        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $entityManager->getRepository(Decklist::class)
            ->findBy(['signature' => $new_signature]);
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                throw new AccessDeniedHttpException('That decklist already exists.');
            }
        }

        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = "Untitled";
        }
        $rawdescription
            = filter_var($request->request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $description = Markdown::defaultTransform($rawdescription);

        $tournament_id = filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT);
        /* @var Tournament $tournament */
        $tournament = $entityManager->getRepository(Tournament::class)->find($tournament_id);

        $decklist = new Decklist();
        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setUser($this->getUser());
        $decklist->setCreation(new DateTime());
        $decklist->setTs(new DateTime());
        $decklist->setSignature($new_signature);
        $decklist->setOutfit($deck->getOutfit());
        $decklist->setGang($deck->getOutfit()->getGang());
        $decklist->setLastPack($deck->getLastPack());
        $decklist->setNbvotes(0);
        $decklist->setNbfavorites(0);
        $decklist->setNbcomments(0);
        $decklist->setTournament($tournament);
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
        } elseif ($deck->getParent()) {
            $decklist->setPrecedent($deck->getParent());
        }
        $decklist->setParent($deck);

        $entityManager->persist($decklist);
        $entityManager->flush();

        return $this->redirect($this->generateUrl('decklist_detail', [
            'decklist_id' => $decklist->getId(),
            'decklist_name' => $decklist->getPrettyName(),
        ]));
    }

    /**
     * @Route(
     *     "/{_locale}/decklists/{type}/{page}",
     *     name="decklists_list",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={
     *         "page"=1
     *     },
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "page"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Decklists $decklists
     * @param $shortCache
     * @param Request $request
     * @param $type
     * @param $code
     * @param $page
     * @return Response
     */
    public function listAction(
        EntityManagerInterface $entityManager,
        Decklists $decklists,
        $shortCache,
        Request $request,
        $type,
        $code = null,
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

        $pagetitle = "Decklists";
        $header = '';

        switch ($type) {
            case 'find':
                $result = $decklists->find($start, $limit, $request);
                $pagetitle = "Decklist search results";
                $header = $this->searchForm($entityManager, $request);
                break;
            case 'favorites':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = ['decklists' => [], 'count' => 0];
                } else {
                    $result = $decklists->favorites($user->getId(), $start, $limit);
                }
                $pagetitle = "Favorite Decklists";
                break;
            case 'mine':
                $response->setPrivate();
                $user = $this->getUser();
                if (! $user) {
                    $result = ['decklists' => [], 'count' => 0];
                } else {
                    $result = $decklists->by_author($user->getId(), $start, $limit);
                }
                $pagetitle = "My Decklists";
                break;
            case 'recent':
                $result = $decklists->recent($start, $limit);
                $pagetitle = "Recent Decklists";
                break;
            case 'halloffame':
                $result = $decklists->halloffame($start, $limit);
                $pagetitle = "Hall of Fame";
                break;
            case 'hottopics':
                $result = $decklists->hottopics($start, $limit);
                $pagetitle = "Hot Topics";
                break;
            case 'tournament':
                $result = $decklists->tournaments($start, $limit);
                $pagetitle = "Tournaments";
                break;
            case 'popular':
            default:
                $result = $decklists->popular($start, $limit);
                $pagetitle = "Popular Decklists";
                break;
        }

        $decklists = $result['decklists'];
        $maxcount = $result['count'];

        $dbh = $entityManager->getConnection();
        $gangs = $dbh->executeQuery(
            "SELECT
            f.name" . /*($request->getLocale() == "en" ? '' : '_' . $request->getLocale()) .*/ " AS name,
            f.code
            FROM gang f
            ORDER BY f.name ASC"
        )->fetchAll();

        $packs = $dbh->executeQuery(
            "SELECT
            p.name" . /*($request->getLocale() == "en" ? '' : '_' . $request->getLocale()) .*/ " AS name,
            p.code
            FROM pack p
            WHERE p.released IS NOT NULL
            ORDER BY p.released DESC
            limit 0,5"
        )->fetchAll();

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

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, $params + ["page" => $page]),
                "current" => $page == $currpage
            ];
        }

        return $this->render(
            'Decklist/decklists.html.twig',
            [
                'pagetitle' => $pagetitle,
                'locales' => $this->renderView('Default/langs.html.twig'),
                'decklists' => $decklists,
                'packs' => $packs,
                'gangs' => $gangs,
                'url' => $request->getRequestUri(),
                'header' => $header,
                'route' => $route,
                'pages' => $pages,
                'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, $params + ["page" => $prevpage]),
                'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, $params + ["page" => $nextpage]),
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/decklist/{decklist_id}/{decklist_name}",
     *     name="decklist_detail",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $shortCache
     * @param $decklist_id
     * @param $decklist_name
     * @return Response
     */
    public function viewAction(
        EntityManagerInterface $entityManager,
        $shortCache,
        $decklist_id,
        $decklist_name
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        $dbh = $entityManager->getConnection();
        $rows = $dbh->executeQuery(
            "SELECT
            d.id,
            d.ts,
            d.name,
            d.prettyname,
            d.creation,
            d.rawdescription,
            d.description,
            d.precedent_decklist_id AS precedent,
            d.tournament_id,
            t.description AS tournament,
            u.id AS user_id,
            u.username,
            u.gang AS usercolor,
            u.reputation,
            u.donation,
            c.code AS outfit_code,
            f.code AS gang_code,
            d.nbvotes,
            d.nbfavorites,
            d.nbcomments
            FROM decklist d
            JOIN user u ON d.user_id = u.id
            JOIN card c ON d.outfit_id = c.id
            JOIN gang f ON d.gang_id = f.id
            LEFT JOIN tournament t ON d.tournament_id = t.id
            WHERE d.id = ?",
            [$decklist_id]
        )->fetchAll();

        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }

        $decklist = $rows[0];
        $comments = $dbh->executeQuery(
            "SELECT
            c.id,
            c.creation,
            c.user_id,
            u.username AS author,
            u.gang AS authorcolor,
            u.donation,
            c.text
            FROM comment c
            JOIN user u ON c.user_id = u.id
            WHERE c.decklist_id = ?
            ORDER BY creation ASC",
            [$decklist_id]
        )->fetchAll();

        $commenters = array_values(array_unique(array_merge([$decklist['username']], array_map(function ($item) {
            return $item['author'];
        }, $comments))));

        $cards = $dbh->executeQuery(
            "SELECT
            c.code AS card_code,
            s.quantity AS qty,
            s.start
            FROM decklistslot s
            JOIN card c ON s.card_id = c.id
            WHERE s.decklist_id = ?
            ORDER BY c.code ASC",
            [$decklist_id]
        )->fetchAll();

        $decklist['comments'] = $comments;
        $decklist['cards'] = $cards;

        $similar_decklists = []; // $this->findSimilarDecklists($entityManager, $decklist_id, 5);
        $precedent_decklists = $dbh->executeQuery(
            "SELECT
            d.id,
            d.name,
            d.prettyname,
            d.nbvotes,
            d.nbfavorites,
            d.nbcomments
            FROM decklist d
            WHERE d.id = ?
            ORDER BY d.creation ASC",
            [$decklist['precedent']]
        )->fetchAll();

        $successor_decklists = $dbh->executeQuery(
            "SELECT
            d.id,
            d.name,
            d.prettyname,
            d.nbvotes,
            d.nbfavorites,
            d.nbcomments
            FROM decklist d
            WHERE d.precedent_decklist_id = ?
            ORDER by d.creation ASC",
            [$decklist_id]
        )->fetchAll();

        $tournaments = $dbh->executeQuery(
            "SELECT
            t.id,
            t.description
            FROM tournament t
            ORDER BY t.description DESC"
        )->fetchAll();

        return $this->render(
            'Decklist/decklist.html.twig',
            [
                'pagetitle' => $decklist['name'],
                'locales' => $this->renderView('Default/langs.html.twig'),
                'decklist' => $decklist,
                'commenters' => $commenters,
                'similar' => $similar_decklists,
                'precedent_decklists' => $precedent_decklists,
                'successor_decklists' => $successor_decklists,
                'tournaments' => $tournaments
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/user/favorite",
     *     name="decklist_favorite",
     *     methods={"POST"}
     * )
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function favoriteAction(EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var Decklist $decklist */
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        if (! $decklist) {
            throw new NotFoundHttpException('Wrong id');
        }
        $author = $decklist->getUser();

        $dbh = $entityManager->getConnection();
        $is_favorite = $dbh->executeQuery(
            "SELECT COUNT(*)
            FROM decklist d
            JOIN favorite f ON f.decklist_id = d.id
            WHERE f.user_id = ?
            AND d.id = ?",
            [$user->getId(), $decklist_id]
        )->fetch(PDO::FETCH_NUM)[0];

        if ($is_favorite) {
            $decklist->setNbfavorites($decklist->getNbfavorites() - 1);
            $user->removeFavorite($decklist);
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() - 5);
            }
        } else {
            $decklist->setNbfavorites($decklist->getNbfavorites() + 1);
            $user->addFavorite($decklist);
            $decklist->setTs(new DateTime());
            if ($author->getId() != $user->getId()) {
                $author->setReputation($author->getReputation() + 5);
            }
        }
        $entityManager->flush();
        return new Response(count($decklist->getFavorites()));
    }

    /**
     * @Route(
     *     "/user/comment",
     *     name="decklist_comment",
     *     methods={"POST"}
     * )
     * @param Swift_Mailer $mailer
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return RedirectResponse
     */
    public function commentAction(Swift_Mailer $mailer, EntityManagerInterface $entityManager, Request $request)
    {
        /* @var User $user */
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        /* @var Decklist $decklist */
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        $comment_text = trim(
            filter_var(
                $request->get('comment'),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            )
        );
        if ($decklist && ! empty($comment_text)) {
            $comment_text = preg_replace(
                '%(?<!\()\b(?:(?:https?|ftp)://)(?:((?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)'
                . '(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))'
                . '(?::\d+)?)(?:[^\s]*)?%iu',
                '[$1]($0)',
                $comment_text
            );

            $mentionned_usernames = [];
            if (preg_match_all('/`@([\w_]+)`/', $comment_text, $matches, PREG_PATTERN_ORDER)) {
                $mentionned_usernames = array_unique($matches[1]);
            }

            $comment_html = Markdown::defaultTransform($comment_text);

            $now = new DateTime();

            $comment = new Comment();
            $comment->setText($comment_html);
            $comment->setCreation($now);
            $comment->setAuthor($user);
            $comment->setDecklist($decklist);

            $entityManager->persist($comment);
            $decklist->setTs($now);
            $decklist->setNbcomments($decklist->getNbcomments() + 1);

            $entityManager->flush();

            // send emails
            $spool = [];
            if ($decklist->getUser()->getNotifAuthor()) {
                if (!isset($spool[$decklist->getUser()->getEmail()])) {
                    $spool[$decklist->getUser()->getEmail()] = 'Emails/newcomment_author.html.twig';
                }
            }
            foreach ($decklist->getComments() as $comment) {
                /* @var $comment Comment */
                $commenter = $comment->getAuthor();
                if ($commenter && $commenter->getNotifCommenter()) {
                    if (!isset($spool[$commenter->getEmail()])) {
                        $spool[$commenter->getEmail()] = 'Emails/newcomment_commenter.html.twig';
                    }
                }
            }
            foreach ($mentionned_usernames as $mentionned_username) {
                /* @var User $mentionned_user */
                $mentionned_user = $entityManager->getRepository(User::class)
                    ->findOneBy(['username' => $mentionned_username]);
                if ($mentionned_user && $mentionned_user->getNotifMention()) {
                    if (!isset($spool[$mentionned_user->getEmail()])) {
                        $spool[$mentionned_user->getEmail()] = 'Emails/newcomment_mentionned.html.twig';
                    }
                }
            }
            unset($spool[$user->getEmail()]);

            $email_data = [
                'username' => $user->getUsername(),
                'decklist_name' => $decklist->getName(),
                'url' => $this->generateUrl(
                    'decklist_detail',
                    [
                        'decklist_id' => $decklist->getId(),
                        'decklist_name' => $decklist->getPrettyname()
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                ) . '#' . $comment->getId(),
                'comment' => $comment_html,
                'profile' => $this->generateUrl('user_profile', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ];
            foreach ($spool as $email => $view) {
                $message = (new Swift_Message("[DoomtownDB] New comment"))
                ->setFrom(["admin@dtdb.co" => $user->getUsername()])
                ->setTo($email)
                ->setBody($this->renderView($view, $email_data), 'text/html');
                $mailer->send($message);
            }
        }

        return $this->redirect($this->generateUrl('decklist_detail', [
            'decklist_id' => $decklist_id,
            'decklist_name' => $decklist->getPrettyName(),
        ]));
    }

    /**
     * @Route(
     *     "/user/comment",
     *     name="decklist_like",
     *     methods={"POST"}
     * )
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function voteAction(EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw new UnauthorizedHttpException('You must be logged in to comment.');
        }

        $decklist_id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);

        /* @var Decklist $decklist */
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        $query = $entityManager->getRepository(Decklist::class)
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
            $decklist->setTs(new DateTime());
            $decklist->setNbvotes($decklist->getNbvotes() + 1);
            $entityManager->flush();
        }

        return new Response(count($decklist->getVotes()));
    }

    /**
     * @Route(
     *     "/decklist/export/text/{decklist_id}",
     *     name="decklist_export_text",
     *     methods={"GET"},
     *     requirements={
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Judge $judge
     * @param $decklist_id
     * @param Request $request
     * @return Response
     */
    public function textexportAction(
        EntityManagerInterface $entityManager,
        Judge $judge,
        $decklist_id,
        Request $request
    ) {
        /* @var Decklist $decklist */
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        if (! $decklist) {
            throw new NotFoundHttpException();
        }

        $classement = $judge->classe($decklist->getCards(), $decklist->getOutfit());

        $lines = [];
        $types = [
            "Dude",
            "Deed",
            "Goods",
            "Spell",
            "Action"
        ];
        $lines[] = "Social export";
        $lines[] = $decklist->getOutfit()->getTitle() . " (" . $decklist->getOutfit()->getPack()->getName() . ")";
        foreach ($types as $type) {
            if (isset($classement[$type]) && $classement[$type]['qty']) {
                $lines[] = "";
                $lines[] = $type . " (" . $classement[$type]['qty'] . ")";
                foreach ($classement[$type]['slots'] as $slot) {
                    $start = "";
                    for ($loop = $slot['start']; $loop > 0; $loop--) {
                        $start .= "*";
                    }
                    $lines[] = $slot['qty'] . "x " . $slot['card']->getTitle() . $start
                        . " (" . $slot['card']->getPack()->getName() . ")";
                }
            }
        }
        $lines[] = "";
        $lines[] = "Cards up to " . $decklist->getLastPack()->getName();
        $content = implode("\r\n", $lines);

        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);

        $response = new Response();

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $name . ".txt");

        $response->setContent($content);
        return $response;
    }

    /**
     * @Route(
     *     "/decklist/export/octgn/{decklist_id}",
     *     name="decklist_export_octgn",
     *     methods={"GET"},
     *     requirements={
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $longCache
     * @param $decklist_id
     * @param Request $request
     * @return Response
     */
    public function octgnexportAction(EntityManagerInterface $entityManager, $longCache, $decklist_id, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);

        /* @var Decklist $decklist */
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        if (! $decklist) {
            throw new NotFoundHttpException();
        }

        $rd = [];
        $start = [];
        $outfit = null;
        $legend = null;
        /** @var Decklistslot $slot */
        foreach ($decklist->getSlots() as $slot) {
            if ($slot->getCard()->getType()->getName() == "Outfit") {
                $outfit = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle()
                ];
            } elseif ($slot->getCard()->getType()->getName() == "Legend") {
                $legend = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle()
                ];
            } elseif ($slot->getStart()) {
                $start[] = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle(),
                    "qty" => $slot->getStart()
                ];
                if ($slot->getQuantity() > $slot->getStart()) {
                    $rd[] = [
                        "id" => $slot->getCard()->getOctgnid(),
                        "name" => $slot->getCard()->getTitle(),
                        "qty" => $slot->getQuantity() - $slot->getStart()
                    ];
                }
            } else {
                $rd[] = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle(),
                    "qty" => $slot->getQuantity()
                ];
            }
        }
        $name = mb_strtolower($decklist->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        if (empty($outfit)) {
            return new Response('no outfit found');
        }
        return $this->octgnexport(
            "$name.o8d",
            $outfit,
            $legend,
            $rd,
            $start,
            $decklist->getRawdescription(),
            $response
        );
    }

    /**
     * @Route(
     *     "/decklist/edit/{decklist_id}",
     *     name="decklist_edit",
     *     methods={"POST"},
     *     requirements={
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $decklist_id
     * @param Request $request
     * @return RedirectResponse
     */
    public function editAction(EntityManagerInterface $entityManager, $decklist_id, Request $request)
    {
        $user = $this->getUser();
        if (! $user) {
            throw new UnauthorizedHttpException("You must be logged in for this operation.");
        }

        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this decklist.");
        }

        $name = trim(filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES));
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = "Untitled";
        }
        $rawdescription = trim(
            filter_var(
                $request->request->get('description'),
                FILTER_SANITIZE_STRING,
                FILTER_FLAG_NO_ENCODE_QUOTES
            )
        );
        $description = Markdown::defaultTransform($rawdescription);

        $tournament_id = filter_var($request->request->get('tournament'), FILTER_SANITIZE_NUMBER_INT);
        $tournament = $entityManager->getRepository(Tournament::class)->find($tournament_id);

        $derived_from = $request->request->get('derived');
        if (preg_match('/^(\d+)$/', $derived_from, $matches)) {
        } elseif (preg_match('/decklist\/(\d+)\//', $derived_from, $matches)) {
            $derived_from = $matches[1];
        } else {
            $derived_from = null;
        }

        if (!$derived_from) {
            $precedent_decklist = null;
        } else {
            /* @var Decklist $precedent_decklist */
            $precedent_decklist = $entityManager->getRepository(Decklist::class)->find($derived_from);
            if (!$precedent_decklist || $precedent_decklist->getCreation() > $decklist->getCreation()) {
                $precedent_decklist = $decklist->getPrecedent();
            }
        }

        $decklist->setName($name);
        $decklist->setPrettyname(preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($name)));
        $decklist->setRawdescription($rawdescription);
        $decklist->setDescription($description);
        $decklist->setPrecedent($precedent_decklist);
        $decklist->setTournament($tournament);
        $decklist->setTs(new DateTime());
        $entityManager->flush();

        return $this->redirect($this->generateUrl('decklist_detail', [
            'decklist_id' => $decklist_id,
            'decklist_name' => $decklist->getPrettyName()
        ]));
    }

    /**
     * @Route(
     *     "/decklist/delete/{decklist_id}",
     *     name="decklist_delete",
     *     methods={"POST"},
     *     requirements={
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $decklist_id
     * @return RedirectResponse
     */
    public function deleteAction(EntityManagerInterface $entityManager, $decklist_id)
    {
        $user = $this->getUser();
        if (! $user) {
            throw new UnauthorizedHttpException("You must be logged in for this operation.");
        }

        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);
        if (! $decklist || $decklist->getUser()->getId() != $user->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this decklist.");
        }

        if ($decklist->getNbvotes() || $decklist->getNbfavorites() || $decklist->getNbcomments()) {
            throw new UnauthorizedHttpException("Cannot delete this decklist.");
        }

        $precedent = $decklist->getPrecedent();

        $children_decks = $decklist->getChildren();
        /* @var Deck $children_deck */
        foreach ($children_decks as $children_deck) {
            $children_deck->setParent($precedent);
        }

        $successor_decklists = $decklist->getSuccessors();
        /* @var Decklist $successor_decklist */
        foreach ($successor_decklists as $successor_decklist) {
            $successor_decklist->setPrecedent($precedent);
        }

        $entityManager->remove($decklist);
        $entityManager->flush();
        return $this->redirect($this->generateUrl('decklists_list', ['type' => 'mine']));
    }


    /**
     * @Route(
     *     "/{_locale}/user/profile/{user_id}/{user_name}/{page}",
     *     name="user_profile_view",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={"page"=1},
     *     requirements={
     *         "user_id"="\d+",
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Decklists $decklists
     * @param $shortCache
     * @param $user_id
     * @param $user_name
     * @param $page
     * @param Request $request
     * @return Response
     */
    public function profileAction(
        EntityManagerInterface $entityManager,
        Decklists $decklists,
        $shortCache,
        $user_id,
        $user_name,
        $page,
        Request $request
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        $user = $entityManager->getRepository(User::class)->find($user_id);
        if (! $user) {
            throw new NotFoundHttpException("No such user.");
        }

        $limit = 100;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        $result = $decklists->by_author($user_id, $start, $limit);

        $lists = $result['decklists'];
        $maxcount = $result['count'];
        $count = count($lists);

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, [
                    "user_id" => $user_id,
                    "user_name" => $user_name,
                    "page" => $page
                ]),
                "current" => $page == $currpage
            ];
        }

        return $this->render(
            'Default/profile.html.twig',
            [
                'pagetitle' => $user->getUsername(),
                'user' => $user,
                'locales' => $this->renderView('Default/langs.html.twig'),
                'decklists' => $lists,
                'url' => $request->getRequestUri(),
                'route' => $route,
                'pages' => $pages,
                'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, [
                    "user_id" => $user_id,
                    "user_name" => $user_name,
                    "page" => $prevpage
                ]),
                'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, [
                    "user_id" => $user_id,
                    "user_name" => $user_name,
                    "page" => $nextpage
                ])
            ],
            $response
        );
    }


    /**
     * @Route(
     *     "/{_locale}/user/comments/{page}",
     *     name="user_comments",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={
     *         "page"=1
     *     },
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $page
     * @param Request $request
     * @return Response
     */
    public function usercommentsAction(EntityManagerInterface $entityManager, $page, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        $user = $this->getUser();

        $limit = 100;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        $dbh = $entityManager->getConnection();

        $comments = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
            c.id,
            c.text,
            c.creation,
            d.id AS decklist_id,
            d.name AS decklist_name,
            d.prettyname AS decklist_prettyname
            FROM comment c
            JOIN decklist d ON c.decklist_id = d.id
            WHERE c.user_id = ?
            ORDER BY creation DESC
            LIMIT $start, $limit",
            [$user->getId()]
        )->fetchAll(PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        $count = count($comments);

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, ["page" => $page]),
                "current" => $page == $currpage
            ];
        }

        return $this->render(
            'Default/usercomments.html.twig',
            [
                'user' => $user,
                'locales' => $this->renderView('Default/langs.html.twig'),
                'comments' => $comments,
                'url' => $request->getRequestUri(),
                'route' => $route,
                'pages' => $pages,
                'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, ["page" => $prevpage]),
                'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, ["page" => $nextpage])
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deckcomments/{page}",
     *     name="all_comments",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={
     *         "page"=1
     *     },
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $shortCache
     * @param $page
     * @param Request $request
     * @return Response
     */
    public function commentsAction(EntityManagerInterface $entityManager, $shortCache, $page, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        $limit = 100;
        if ($page < 1) {
            $page = 1;
        }
        $start = ($page - 1) * $limit;

        $dbh = $entityManager->getConnection();

        $comments = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
            c.id,
            c.text,
            c.creation,
            d.id AS decklist_id,
            d.name AS decklist_name,
            d.prettyname AS decklist_prettyname,
            u.id AS user_id,
            u.username AS author
            FROM comment c
            JOIN decklist d ON c.decklist_id = d.id
            JOIN user u ON c.user_id = u.id
            ORDER BY creation DESC
            LIMIT $start, $limit",
            []
        )->fetchAll(PDO::FETCH_ASSOC);

        $maxcount = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        $count = count($comments);

        // pagination : calcul de nbpages // currpage // prevpage // nextpage
        // à partir de $start, $limit, $count, $maxcount, $page

        $currpage = $page;
        $prevpage = max(1, $currpage - 1);
        $nbpages = min(10, ceil($maxcount / $limit));
        $nextpage = min($nbpages, $currpage + 1);

        $route = $request->get('_route');

        $pages = [];
        for ($page = 1; $page <= $nbpages; $page++) {
            $pages[] = [
                "numero" => $page,
                "url" => $this->generateUrl($route, ["page" => $page]),
                "current" => $page == $currpage
            ];
        }

        return $this->render(
            'Default/allcomments.html.twig',
            [
                'locales' => $this->renderView('Default/langs.html.twig'),
                'comments' => $comments,
                'url' => $request->getRequestUri(),
                'route' => $route,
                'pages' => $pages,
                'prevurl' => $currpage == 1 ? null : $this->generateUrl($route, ["page" => $prevpage]),
                'nexturl' => $currpage == $nbpages ? null : $this->generateUrl($route, ["page" => $nextpage])
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/decklists/search",
     *     name="decklists_searchform",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $longCache
     * @param Request $request
     * @return Response
     */
    public function searchAction(EntityManagerInterface $entityManager, $longCache, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);

        $dbh = $entityManager->getConnection();
        $gangs = $dbh->executeQuery(
            "SELECT
            f.name,
            f.code
            FROM gang f
            ORDER BY f.name ASC"
        )->fetchAll();

        $packs = $dbh->executeQuery(
            "SELECT
            p.name,
            p.code,
            '' AS selected
            FROM pack p
            WHERE p.released IS NOT NULL
            ORDER BY p.released DESC"
        )->fetchAll();

        return $this->render(
            'Search/search.html.twig',
            [
                'pagetitle' => 'Decklist Search',
                'url' => $request->getRequestUri(),
                'gangs' => $gangs,
                'form' => $this->renderView(
                    'Search/form.html.twig',
                    [
                        'packs' => $packs,
                        'author' => '',
                        'title' => ''
                    ]
                ),
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/donators",
     *     name="donators",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $shortCache
     * @return Response
     */
    public function donatorsAction(EntityManagerInterface $entityManager, $shortCache)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);
        $dbh = $entityManager->getConnection();
        $users = $dbh->executeQuery("SELECT * FROM user WHERE donation > 0 ORDER BY donation DESC, username", [])
            ->fetchAll(PDO::FETCH_ASSOC);
        return $this->render(
            'Default/donators.html.twig',
            [
                'pagetitle' => 'The Gracious Donators',
                'donators' => $users
            ],
            $response
        );
    }

    /**
     * @param $filename
     * @param $outfit
     * @param $legend
     * @param $rd
     * @param $start
     * @param $description
     * @param Response $response
     * @return Response
     */
    protected function octgnexport($filename, $outfit, $legend, $rd, $start, $description, Response $response)
    {
        $content = $this->renderView('octgn.xml.twig', [
            "outfit" => $outfit,
            "legend" => $legend,
            "start" => $start,
            "deck" => $rd,
            "description" => strip_tags($description)
        ]);

        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);

        $response->setContent($content);
        return $response;
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return string
     */
    protected function searchForm(EntityManagerInterface $entityManager, Request $request)
    {
        $cards_code = $request->query->get('cards');
        $gang_code = filter_var($request->query->get('gang'), FILTER_SANITIZE_STRING);
        $lastpack_code = filter_var($request->query->get('lastpack'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_title = filter_var($request->query->get('title'), FILTER_SANITIZE_STRING);
        $sort = $request->query->get('sort');
        $dbh = $entityManager->getConnection();
        $packs = $dbh->executeQuery(
            "SELECT
            p.name,
            p.code,
            '' AS selected
            FROM pack p
            WHERE p.released IS NOT NULL
            ORDER BY p.released DESC"
        )->fetchAll();

        foreach ($packs as $i => $pack) {
            $packs[$i]['selected'] = ($pack['code'] == $lastpack_code) ? ' selected="selected"' : '';
        }
        $params = [
            'packs' => $packs,
            'author' => $author_name,
            'title' => $decklist_title
        ];
        $params['sort_' . $sort] = ' selected="selected"';
        $params['gang_' . substr($gang_code, 0, 1)] = ' selected="selected"';

        if (! empty($cards_code) && is_array($cards_code)) {
            $cards = $dbh->executeQuery(
                "SELECT
                c.title,
                c.code,
                f.code gang_code
                FROM card c
                JOIN gang f ON f.id = c.gang_id
                WHERE c.code IN (?)
                ORDER BY c.code DESC",
                [$cards_code],
                [Connection::PARAM_INT_ARRAY]
            )->fetchAll();

            $params['cards'] = '';
            foreach ($cards as $card) {
                $params['cards'] .= $this->renderView('Search/card.html.twig', $card);
            }
        }
        return $this->renderView('Search/form.html.twig', $params);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param $decklist_id
     * @param $number
     * @return array
     */
    protected function findSimilarDecklists(EntityManagerInterface $entityManager, $decklist_id, $number)
    {
        $dbh = $entityManager->getConnection();
        $list = $dbh->executeQuery(
            "SELECT
            l.id,
            (
                SELECT COUNT(s.id)
                FROM decklistslot s
                WHERE (
                    s.decklist_id = l.id
                    AND s.card_id NOT IN (
                        SELECT t.card_id
                        FROM decklistslot t
                        WHERE t.decklist_id = ?
                    )
                )
                OR
                (
                    s.decklist_id = ?
                    AND s.card_id NOT IN (
                        SELECT t.card_id
                        FROM decklistslot t
                        WHERE t.decklist_id = l.id
                    )
                )
            ) difference
             FROM decklist l
            WHERE l.id != ?
            ORDER BY difference ASC
            LIMIT 0, ?",
            [$decklist_id, $decklist_id, $decklist_id, $number]
        )->fetchAll();

        $arr = [];
        foreach ($list as $item) {
            $rows = $dbh->executeQuery(
                "SELECT
                d.id,
                d.name,
                d.prettyname,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                FROM decklist d
                WHERE d.id = ?
                ",
                [$item["id"]]
            )->fetchAll();

            $decklist = $rows[0];
            $arr[] = $decklist;
        }
        return $arr;
    }
}
