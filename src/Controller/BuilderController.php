<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Deckchange;
use App\Entity\Decklist;
use App\Entity\Type;
use App\Services\Decks;
use App\Services\Judge;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use ZipArchive;

class BuilderController extends AbstractController
{
    /**
     * @Route(
     *     "/{_locale}/deck/new",
     *     name="deck_buildform",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param int $longCache
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function buildformAction($longCache, EntityManagerInterface $entityManager)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);

        $type = $entityManager->getRepository(Type::class)->findOneBy(["name" => "Outfit"]);
        $identities = $entityManager->getRepository(Card::class)->findBy(
            ["type" => $type],
            ["gang" => "ASC", "title" => "ASC"]
        );

        return $this->render(
            'Builder/initbuild.html.twig',
            [
                'pagetitle' => "New deck",
                'locales' => $this->renderView('Default/langs.html.twig'),
                "identities" => array_filter(
                    $identities,
                    function ($iden) {
                        return $iden->getPack()
                            ->getCode() != "alt";
                    }
                ),
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deck/build/{card_code}",
     *     name="deck_initbuild",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param int $longCache
     * @param EntityManagerInterface $entityManager
     * @param string $card_code
     * @return Response
     */
    public function initbuildAction(
        $longCache,
        EntityManagerInterface $entityManager,
        $card_code
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);

        /* @var $card Card */
        $card = $entityManager
            ->getRepository(Card::class)
            ->findOneBy(["code" => $card_code]);

        if (! $card) {
            return new Response('card not found.');
        }
        $arr = [
            $card_code => [
                'quantity' => 1,
                'start' => 0,
            ]
        ];

        return $this->render(
            'Builder/deck.html.twig',
            [
                'pagetitle' => "Deckbuilder",
                'locales' => $this->renderView('Default/langs.html.twig'),
                'deck' => [
                    "slots" => $arr,
                    "name" => "New Deck",
                    "description" => "",
                    "tags" => $card->getGang()->getCode(),
                    "id" => "",
                    "history" => [],
                    "unsaved" => 0,
                ],
                "published_decklists" => []
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deck/import",
     *     name="deck_import",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param int $longCache
     * @param SessionInterface $session
     * @return Response
     */
    public function importAction($longCache, SessionInterface $session)
    {
        // the deck import code is broken. for the time being, send the user back to their "my decks" page.
        // @todo fix this or throw this out. [ST 2023/01/12]
        $session->getFlashBag()->set('error', "Deck import is currently not available.");
        return $this->redirect($this->generateUrl('decks_list'));

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);

        return $this->render(
            'Builder/directimport.html.twig',
            [
                'pagetitle' => "Import a deck",
                'locales' => $this->renderView('Default/langs.html.twig')
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deck/fileimport",
     *     name="deck_fileimport",
     *     locale="en",
     *     methods={"POST"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function fileimportAction(EntityManagerInterface $entityManager, Request $request)
    {
        // the deck import code is broken. for the time being, send the user back to their "my decks" page.
        // @todo fix this or throw this out. [ST 2023/01/12]
        throw new NotFoundHttpException('Deck upload via file import is currently not available.');
        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('upfile');
        if (! isset($uploadedFile)) {
            return new Response('No file');
        }
        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();

        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);

            // check to see if the mime-type starts with 'text'
            $is_text = substr(finfo_file($finfo, $filename), 0, 4) == 'text'
                || substr(finfo_file($finfo, $filename), 0, 15) == "application/xml";
            if (! $is_text) {
                return new Response('Bad file');
            }
        }

        if ($filetype == "octgn" || ($filetype == "auto" && $origext == "o8d")) {
            $parse = $this->parseOctgnImport($entityManager, file_get_contents($filename));
        } else {
            $parse = $this->parseTextImport($entityManager, file_get_contents($filename));
        }
        return $this->forward(
            'App\Controller\BuilderController::saveAction',
            [
                'name' => $origname,
                'content' => json_encode($parse['content']),
                'description' => $parse['description'],
            ]
        );
    }

    /**
     * @Route(
     *     "/deck/export/text/{deck_id}",
     *     name="deck_export_text",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "deck_id"="\d+",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Judge $judge
     * @param string $deck_id
     * @return Response
     */
    public function textexportAction(EntityManagerInterface $entityManager, Judge $judge, $deck_id)
    {
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        //if (! $this->getUser() || $this->getUser()->getId() != $deck->getUser()->getId())
        //    throw new UnauthorizedHttpException("You don't have access to this deck.");

        $classement = $judge->classe($deck->getCards(), $deck->getOutfit());
        $lines = [];
        $types = ["Dude", "Deed", "Goods", "Spell", "Action"];

        $lines[] = $deck->getOutfit()->getTitle() . " (" . $deck->getOutfit()
            ->getPack()
            ->getName() . ")";
        foreach ($types as $type) {
            if (isset($classement[$type]) && $classement[$type]['qty']) {
                $lines[] = "";
                $lines[] = $type . " (" . $classement[$type]['qty'] . ")";
                foreach ($classement[$type]['slots'] as $slot) {
                    $start = "";
                    for ($loop = $slot['start']; $loop > 0; $loop--) {
                        $start .= "*";
                    }
                    $lines[] = $slot['qty'] . "x " . $slot['card']->getTitle()
                        . $start . " (" . $slot['card']->getPack()->getName() . ")";
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

    /**
     * @Route(
     *     "/deck/export/octgn/{deck_id}",
     *     name="deck_export_octgn",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "deck_id"="\d+",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param string $deck_id
     * @return Response
     */
    public function octgnexportAction(EntityManagerInterface $entityManager, $deck_id)
    {
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        //if (! $this->getUser() || $this->getUser()->getId() != $deck->getUser()->getId())
        //    throw new UnauthorizedHttpException("You don't have access to this deck.");

        $rd = [];
        $start = [];
        $outfit = null;
        $legend = null;
        foreach ($deck->getSlots() as $slot) {
            if ($slot->getCard()->getType()->getName() === "Outfit") {
                $outfit = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle(),
                ];
            } elseif ($slot->getCard()->getType()->getName() === "Legend") {
                $legend = [
                    "id" => $slot->getCard()->getOctgnid(),
                    "name" => $slot->getCard()->getTitle(),
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
                        "qty" => $slot->getQuantity() - $slot->getStart(),
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
        $name = mb_strtolower($deck->getName());
        $name = preg_replace('/[^a-zA-Z0-9_\-]/', '-', $name);
        $name = preg_replace('/--+/', '-', $name);
        if (empty($outfit)) {
            return new Response('no outfit found');
        }
        return $this->octgnexport("$name.o8d", $outfit, $legend, $start, $rd, $deck->getDescription());
    }

    /**
     * @Route(
     *     "/{_locale}/deck/save",
     *     name="deck_save",
     *     locale="en",
     *     methods={"POST"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param Decks $decks
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @return Response
     */
    public function saveAction(Decks $decks, EntityManagerInterface $entityManager, Request $request)
    {
        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            return new Response(
                'You have reached the maximum number of decks allowed. Delete some decks or increase your reputation.'
            );
        }

        $id = filter_var($request->get('id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = null;
        $source_deck = null;
        if ($id) {
            $deck = $entityManager->getRepository(Deck::class)->find($id);
            if (!$deck || $user->getId() != $deck->getUser()->getId()) {
                throw new UnauthorizedHttpException("You don't have access to this deck.");
            }
            $source_deck = $deck;
        }

        $cancel_edits = (bool) filter_var($request->get('cancel_edits'), FILTER_SANITIZE_NUMBER_INT);
        if ($cancel_edits) {
            if ($deck) {
                $decks->revertDeck($deck);
            }
            return $this->redirect($this->generateUrl('decks_list'));
        }

        $is_copy = (bool) filter_var($request->get('copy'), FILTER_SANITIZE_NUMBER_INT);
        if ($is_copy || !$id) {
            $deck = new Deck();
        }

        $content = json_decode($request->get('content'), true);
        if (! count($content)) {
            return new Response('Cannot import empty deck');
        }

        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);

        $decks->saveDeck(
            $this->getUser(),
            $deck,
            $decklist_id,
            $name,
            $description,
            $tags,
            $content,
            $source_deck ? $source_deck : null
        );
        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route(
     *     "/{_locale}/deck/delete",
     *     name="deck_delete",
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
    public function deleteAction(EntityManagerInterface $entityManager, SessionInterface $session, Request $request)
    {
        $deck_id = filter_var($request->get('deck_id'), FILTER_SANITIZE_NUMBER_INT);
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        if (! $deck) {
            return $this->redirect($this->generateUrl('decks_list'));
        }
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }

        foreach ($deck->getChildren() as $decklist) {
            $decklist->setParent(null);
        }
        $entityManager->remove($deck);
        $entityManager->flush();

        $session->getFlashBag()->set('notice', "Deck deleted.");
        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route(
     *     "/{_locale}/deck/delete_list",
     *     name="deck_delete_list",
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
    public function deleteListAction(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        Request $request
    ) {
        $list_id = explode('-', $request->get('ids'));
        $repository = $entityManager->getRepository(Deck::class);
        foreach ($list_id as $id) {
            $deck = $repository->find($id);
            if (!$deck) {
                continue;
            }
            if ($this->getUser()->getId() != $deck->getUser()->getId()) {
                continue;
            }

            foreach ($deck->getChildren() as $decklist) {
                $decklist->setParent(null);
            }
            $entityManager->remove($deck);
        }
        $entityManager->flush();

        $session->getFlashBag()->set('notice', "Decks deleted.");

        return $this->redirect($this->generateUrl('decks_list'));
    }

    /**
     * @Route(
     *     "/{_locale}/deck/edit/{deck_id}",
     *     name="deck_edit",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "deck_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param string $deck_id
     * @return Response
     */
    public function editAction(EntityManagerInterface $entityManager, $deck_id)
    {
        $dbh = $entityManager->getConnection();
        $rows = $dbh->executeQuery(
            "SELECT
            d.id,
            d.name,
            d.description,
            DATE_FORMAT(d.datecreation, '%Y-%m-%dT%TZ') AS datecreation,
            DATE_FORMAT(d.dateupdate, '%Y-%m-%dT%TZ') AS dateupdate,
            (SELECT COUNT(*) FROM deckchange c WHERE c.deck_id = d.id AND c.saved = 0) AS unsaved,
            d.tags
            FROM deck d
            WHERE d.id = ?
            ",
            [$deck_id]
        )->fetchAll();

        $deck = $rows[0];

        $rows = $dbh->executeQuery(
            "SELECT
            c.code,
            s.quantity,
            s.start
            FROM deckslot s
            JOIN card c ON s.card_id = c.id
            WHERE s.deck_id = ?",
            [$deck_id]
        )->fetchAll();

        $cards = [];
        foreach ($rows as $row) {
            $cards[$row['code']] = [
                "quantity" => intval($row['quantity']),
                "start" => intval($row['start']),
            ];
        }

        $snapshots = [];
        $changes = $dbh->executeQuery(
            "SELECT
            DATE_FORMAT(c.datecreation, '%Y-%m-%dT%TZ') AS datecreation,
            c.variation,
            c.saved
            FROM deckchange c
            WHERE c.deck_id = ? AND c.saved = 1
            ORDER BY datecreation DESC",
            [$deck_id]
        )->fetchAll();

        // recreating the versions with the variation info, starting from $preversion
        $preversion = $cards;
        foreach ($changes as $change) {
            $change['variation'] = $variation = json_decode($change['variation'], true);
            $change['saved'] = (bool) $change['saved'];
            // add preversion with variation that lead to it
            $change['content'] = $preversion;
            array_unshift($snapshots, $change);
            // applying variation to create 'next' (older) preversion
            foreach ($variation[0] as $code => $qty) {
                $preversion[$code]["quantity"] = $preversion[$code]["quantity"] - $qty;
                if ($preversion[$code]["quantity"] == 0) {
                    unset($preversion[$code]);
                }
            }
            foreach ($variation[1] as $code => $qty) {
                if (!isset($preversion[$code])) {
                    $preversion[$code] = ["quantity" => 0, "start" => 0];
                }
                $preversion[$code]["quantity"] = $preversion[$code]["quantity"] + $qty;
            }
            ksort($preversion);
        }
        // add last know version with empty diff
        $change['content'] = $preversion;
        $change['datecreation'] = $deck['datecreation'];
        $change['saved'] = true;
        $change['variation'] = null;
        array_unshift($snapshots, $change);
        $changes = $dbh->executeQuery(
            "SELECT
            DATE_FORMAT(c.datecreation, '%Y-%m-%dT%TZ') AS datecreation,
            c.variation,
            c.saved
            FROM deckchange c
            WHERE c.deck_id = ? AND c.saved = 0
            ORDER BY datecreation ASC",
            [$deck_id]
        )->fetchAll();

        // recreating the snapshots with the variation info, starting from $postversion
        $postversion = $cards;
        foreach ($changes as $change) {
            $change['variation'] = $variation = json_decode($change['variation'], true);
            $change['saved'] = (bool) $change['saved'];
            // applying variation to postversion
            foreach ($variation[0] as $code => $qty) {
                if (!isset($postversion[$code])) {
                    $postversion[$code] = ["quantity" => 0, "start" => 0];
                }
                $postversion[$code]["quantity"] = $postversion[$code]["quantity"] + $qty;
            }
            foreach ($variation[1] as $code => $qty) {
                $postversion[$code]["quantity"] = $postversion[$code]["quantity"] - $qty;
                if ($postversion[$code]["quantity"] == 0) {
                    unset($postversion[$code]);
                }
            }
            ksort($postversion);
            // add postversion with variation that lead to it
            $change['content'] = $postversion;
            array_push($snapshots, $change);
        }

        // current deck is newest snapshot
        $deck['slots'] = $postversion;

        // history is deck contents without 'start' key
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
            order by d.creation asc",
            [$deck_id]
        )->fetchAll();

        return $this->render(
            'Builder/deck.html.twig',
            [
                'pagetitle' => "Deckbuilder",
                'locales' => $this->renderView('Default/langs.html.twig'),
                'deck' => $deck,
                'published_decklists' => $published_decklists
            ]
        );
    }


    /**
     * @Route(
     *     "/{_locale}/deck/view/{deck_id}",
     *     name="deck_view",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "deck_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Judge $judge
     * @param string $deck_id
     * @return Response
     */
    public function viewAction(
        EntityManagerInterface $entityManager,
        Judge $judge,
        $deck_id
    ) {
        $dbh = $entityManager->getConnection();
        $rows = $dbh->executeQuery(
            "SELECT
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
            ",
            [$deck_id]
        )->fetchAll();

        $deck = $rows[0];

        $rows = $dbh->executeQuery(
            "SELECT
            c.code,
            s.quantity,
            s.start
            from deckslot s
            join card c on s.card_id=c.id
            where s.deck_id=?",
            [$deck_id]
        )->fetchAll();

        $cards = [];
        foreach ($rows as $row) {
            $cards[$row['code']] = [
                "quantity" => intval($row['quantity']),
                "start" => intval($row['start']),
            ];
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
            FROM decklist d
            WHERE d.parent_deck_id = ?
            ORDER BY d.creation ASC",
            [$deck_id]
        )->fetchAll();

        $tournaments = $dbh->executeQuery(
            "SELECT
            t.id,
            t.description
            FROM tournament t
            ORDER BY t.description DESC"
        )->fetchAll();

        $problem = $deck['problem'];
        $deck['message'] = isset($problem) ? $judge->problem($problem) : '';

        return $this->render(
            'Builder/deckview.html.twig',
            [
                'pagetitle' => "Deckbuilder",
                'locales' => $this->renderView('Default/langs.html.twig'),
                'deck' => $deck,
                'published_decklists' => $published_decklists,
                'tournaments' => $tournaments
            ]
        );
    }

    /**
     * @Route(
     *     "/{_locale}/decks",
     *     name="decks_list",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Decks $decks
     * @return Response
     */
    public function listAction(EntityManagerInterface $entityManager, Decks $decks)
    {
        $user = $this->getUser();
        $tournaments = $entityManager->getConnection()->executeQuery(
            "SELECT
            t.id,
            t.description
            FROM tournament t
            ORDER BY t.description desc"
        )->fetchAll();

        return $this->render(
            'Builder/decks.html.twig',
            [
                'pagetitle' => "My Decks",
                'locales' => $this->renderView('Default/langs.html.twig'),
                'decks' => $decks->getByUser($user),
                'nbmax' => $user->getMaxNbDecks(),
                'tournaments' => $tournaments,
            ]
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deck/copy/{decklist_id}",
     *     name="deck_copy",
     *     locale="en",
     *     methods={"POST"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param string $decklist_id
     * @return Response
     */
    public function copyAction(EntityManagerInterface $entityManager, $decklist_id)
    {
        $decklist = $entityManager->getRepository(Decklist::class)->find($decklist_id);

        $content = [];
        foreach ($decklist->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = [
                "quantity" => $slot->getQuantity(),
                "start" => $slot->getStart(),
            ];
        }
        return $this->forward(
            'App\Controller\BuilderController::saveAction',
            [
                'name' => $decklist->getName(),
                'content' => json_encode($content),
                'decklist_id' => $decklist_id,
            ]
        );
    }

    /**
     * @Route(
     *     "/{_locale}/deck/duplicate/{deck_id}",
     *     name="deck_duplicate",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *         "deck_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param string $deck_id
     * @return Response
     */
    public function duplicateAction(EntityManagerInterface $entityManager, $deck_id)
    {
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);

        $content = [];
        foreach ($deck->getSlots() as $slot) {
            $content[$slot->getCard()->getCode()] = [
                "quantity" => $slot->getQuantity(),
                "start" => $slot->getStart(),
            ];
        }
        return $this->forward(
            'App\Controller\BuilderController::saveAction',
            [
                'name' => $deck->getName() . ' (copy)',
                'content' => json_encode($content),
                'deck_id' => $deck->getParent() ? $deck->getParent()->getId() : null,
            ]
        );
    }

    /**
     * @Route("/deck/export/all", name="decks_download_all", methods={"GET"})
     * @param EntityManagerInterface $entityManager
     * @param Decks $decks
     * @return Response
     */
    public function downloadallAction(EntityManagerInterface $entityManager, Decks $decks)
    {
        $user = $this->getUser();
        $decks = $decks->getByUser($user);
        $file = tempnam(sys_get_temp_dir(), "zip");
        $zip = new ZipArchive();
        $res = $zip->open($file, ZipArchive::OVERWRITE);
        $repository = $entityManager->getRepository(Card::class);
        if ($res === true) {
            foreach ($decks as $deck) {
                $content = [];
                foreach ($deck['cards'] as $slot) {
                    $card = $repository->findOneBy(['code' => $slot['card_code']]);
                    if (!$card) {
                        continue;
                    }
                    $cardtitle = $card->getTitle();
                    $packname = $card->getPack()->getName();
                    $qty = $slot['qty'];
                    $content[] = "$cardtitle ($packname) x$qty";
                }
                $filename = str_replace('/', ' ', $deck['name']) . '.txt';
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

    /**
     * @Route("/deck/import/all", name="decks_upload_all", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     * @param Decks $decks
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function uploadallAction(
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        Decks $decks,
        Request $request
    ) {
        // the deck import code is broken. for the time being, send the user back to their "my decks" page.
        // @todo fix this or throw this out. [ST 2023/01/12]
        $session->getFlashBag()->set('error', "Deck upload is currently not available.");
        return $this->redirect($this->generateUrl('decks_list'));

        // time-consuming task
        ini_set('max_execution_time', 300);

        $filetype = filter_var($request->get('type'), FILTER_SANITIZE_STRING);
        $uploadedFile = $request->files->get('uparchive');
        if (! isset($uploadedFile)) {
            return new Response('No file');
        }

        $origname = $uploadedFile->getClientOriginalName();
        $origext = $uploadedFile->getClientOriginalExtension();
        $filename = $uploadedFile->getPathname();

        if (function_exists("finfo_open")) {
            // return mime type ala mimetype extension
            $finfo = finfo_open(FILEINFO_MIME);
            // check to see if the mime-type is 'zip'
            if (substr(finfo_file($finfo, $filename), 0, 15) !== 'application/zip') {
                return new Response('Bad file');
            }
        }
        $zip = new ZipArchive();
        $res = $zip->open($filename);
        if ($res === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                 $name = $zip->getNameIndex($i);
                 $parse = $this->parseTextImport($entityManager, $zip->getFromIndex($i));
                 $deck = new Deck();
                 $decks->saveDeck(
                     $this->getUser(),
                     $deck,
                     null,
                     $name,
                     '',
                     '',
                     $parse['content'],
                     null,
                 );
            }
        }
        $zip->close();
        $session->getFlashBag()->set('notice', "Decks imported.");
        return $this->redirect($this->generateUrl('decks_list'));
    }


    /**
     * @Route("/deck/autosave/{deck_id}", name="deck_autosave", methods={"POST"})
     * @param EntityManagerInterface $entityManager
     * @param string $deck_id
     * @param Request $request
     * @return Response
     */
    public function autosaveAction(EntityManagerInterface $entityManager, $deck_id, Request $request)
    {
        $user = $this->getUser();
        /* @var Deck $deck */
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        if (!$deck) {
            throw new BadRequestHttpException("Cannot find deck " . $deck_id);
        }
        if ($user->getId() != $deck->getUser()->getId()) {
            throw new UnauthorizedHttpException("You don't have access to this deck.");
        }
        $diff = json_decode($request->get('diff'), true);
        if (count($diff) != 2) {
            throw new BadRequestHttpException("Wrong content " . $diff);
        }
        if (count($diff[0]) || count($diff[1])) {
            $change = new Deckchange();
            $change->setDeck($deck);
            $change->setVariation(json_encode($diff));
            $change->setSaved(false);
            $entityManager->persist($change);
            $entityManager->flush();
        }
        return new Response($change->getDatecreation()->format('c'));
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param $text
     * @return array
     */
    protected function parseTextImport(EntityManagerInterface $entityManager, $text)
    {
        $content = [];
        $lines = explode("\n", $text);
        $outfit = null;
        foreach ($lines as $line) {
            if (preg_match('/^\s*(\d)x?([\pLl\pLu\pN\-\.\'\!\: ]+)/u', $line, $matches)) {
                $quantity = intval($matches[1]);
                $name = trim($matches[2]);
            } elseif (preg_match('/^([^\(]+).*x(\d)/', $line, $matches)) {
                $quantity = intval($matches[2]);
                $name = trim($matches[1]);
            } elseif (empty($outfit) && preg_match('/([^\(]+):([^\(]+)/', $line, $matches)) {
                $quantity = 1;
                $name = trim($matches[1] . ":" . $matches[2]);
                $outfit = $name;
            } else {
                continue;
            }
            $card = $entityManager->getRepository(Card::class)->findOneBy(['title' => $name]);
            if ($card) {
                $content[$card->getCode()] = $quantity;
            }
        }
        return [
            "content" => $content,
            "description" => ""
        ];
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param $octgn
     * @return array
     */
    protected function parseOctgnImport(EntityManagerInterface $entityManager, $octgn)
    {
        $crawler = new Crawler();
        $crawler->addXmlContent($octgn);
        $cardcrawler = $crawler->filter('deck > section > card');

        $content = [];
        foreach ($cardcrawler as $domElement) {
            $quantity = intval($domElement->getAttribute('qty'));
            $card = $entityManager->getRepository(Card::class)->findOneBy([
                'octgnid' => $domElement->getAttribute('id'),
            ]);
            if ($card) {
                $content[$card->getCode()]
                    = (isset($content[$card->getCode()]) ? $content[$card->getCode()] : 0) + $quantity;
            }
        }

        $desccrawler = $crawler->filter('deck > notes');
        $description = [];
        foreach ($desccrawler as $domElement) {
            $description[] = $domElement->nodeValue;
        }
        return [
            "content" => $content,
            "description" => implode("\n", $description),
        ];
    }

    /**
     * @param $filename
     * @param $outfit
     * @param $legend
     * @param $start
     * @param $rd
     * @param $description
     * @return Response
     */
    protected function octgnexport($filename, $outfit, $legend, $start, $rd, $description)
    {
        $content = $this->renderView(
            'Builder/octgn.xml.twig',
            [
                "outfit" => $outfit,
                "legend" => $legend,
                "start" => $start,
                "deck" => $rd,
                "description" => strip_tags($description)
            ]
        );

        $response = new Response();

        $response->headers->set('Content-Type', 'application/octgn');
        $response->headers->set('Content-Disposition', 'attachment;filename=' . $filename);

        $response->setContent($content);
        return $response;
    }
}
