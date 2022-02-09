<?php

namespace App\Controller;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Decklistslot;
use App\Entity\Pack;
use App\Services\CardsData;
use App\Services\Decks;
use App\Services\Judge;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Michelf\Markdown;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Yectep\PhpSpreadsheetBundle\Factory;

class ApiController extends AbstractController
{
    /**
     * @Route("/api/sets", name="api_sets", methods={"GET"})
     * @param int $longCache
     * @param Request $request
     * @return Response
     */
    public function setsAction(CardsData $cardsData, $longCache, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }

        $data = $cardsData->allsetsnocycledata();

        $content = json_encode($data);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }
        $response->setContent($content);
        return $response;
    }

    /**
     * @Route(
     *     "/api/card/{card_code}.{_format}",
     *     name="api_card",
     *     format="json",
     *     methods={"GET"},
     *     requirements={
     *         "_format"="json",
     *         "card_code"="\d+"
     *     }
     * )
     * @param CardsData $cardsData
     * @param $card_code
     * @param Request $request
     * @return Response
     */
    public function cardAction(CardsData $cardsData, $card_code, $longCache, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }
        $conditions = $cardsData->syntax($card_code);
        $cardsData->validateConditions($conditions);
        $query = $cardsData->buildQueryFromConditions($conditions);

        $cards = [];
        $last_modified = null;
        if ($query && $rows = $cardsData->get_search_rows($conditions, "set")) {
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) {
                    $last_modified = $rows[$rowindex]->getTs();
                }
            }
            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                $card = $cardsData->getCardInfo($rows[$rowindex], true, "en");
                $cards[] = $card;
            }
        }
        $content = json_encode($cards);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
        }
        $response->headers->set('Content-Type', 'application/javascript');
        $response->setContent($content);
        return $response;
    }

    /**
     * @Route("/api/cards", name="api_cards", methods={"GET"})
     * @param CardsData $cardsData
     * @param int $longCache
     * @param Request $request
     * @return Response
     */
    public function cardsAction(CardsData $cardsData, $longCache, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);
        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }
        $cards = [];
        $last_modified = null;
        if ($rows = $cardsData->get_search_rows([], "set", true)) {
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) {
                    $last_modified = $rows[$rowindex]->getTs();
                }
            }
            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                $card = $cardsData->getCardInfo($rows[$rowindex], true);
                $cards[] = $card;
            }
        }

        $content = json_encode($cards);
        if (isset($jsonp)) {
            $content = "$jsonp($content)";
            $response->headers->set('Content-Type', 'application/javascript');
        } else {
            $response->headers->set('Content-Type', 'application/json');
        }

        $response->setContent($content);
        return $response;
    }

    /**
     * @Route(
     *     "/api/set/{pack_code}.{_format}",
     *     name="api_set",
     *     format="json",
     *     methods={"GET"},
     *     requirements={
     *         "_format"="json|xml|xlsx"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Factory $factory
     * @param CardsData $cardsData
     * @param string $pack_code
     * @param int $longCache
     * @param Request $request
     * @return Response|StreamedResponse|void
     */
    public function setAction(
        EntityManagerInterface $entityManager,
        Factory $factory,
        CardsData $cardsData,
        $pack_code,
        $longCache,
        Request $request
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }
        $format = $request->getRequestFormat();

        $pack = $entityManager->getRepository(Pack::class)->findOneBy(['code' => $pack_code]);
        if (!$pack) {
            die();
        }

        $conditions = $cardsData->syntax("e:$pack_code");
        $cardsData->validateConditions($conditions);
        $query = $this->get('cards_data')->buildQueryFromConditions($conditions);

        $cards = [];
        $last_modified = new DateTime();
        if ($query && $rows = $cardsData->get_search_rows($conditions, "set")) {
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                if (empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) {
                    $last_modified = $rows[$rowindex]->getTs();
                }
            }
            $response->setLastModified($last_modified);
            if ($response->isNotModified($request)) {
                return $response;
            }
            for ($rowindex = 0; $rowindex < count($rows); $rowindex++) {
                $card = $cardsData->getCardInfo($rows[$rowindex], true, "en");
                $cards[] = $card;
            }
        }

        if ($format == "json") {
            $content = json_encode($cards);
            if (isset($jsonp)) {
                $content = "$jsonp($content)";
                $response->headers->set('Content-Type', 'application/javascript');
            } else {
                $response->headers->set('Content-Type', 'application/json');
            }
            $response->setContent($content);
/*
        } elseif ($format == "xml") {
            $cardsxml = [];
            foreach($cards as $card) {
                if(!isset($card['keywords'])) $card['keywords'] = "";
                if($card['uniqueness']) $card['keywords'] .= empty($card['keywords']) ? "Unique" : " - Unique";
                $card['keywords'] = str_replace(' - ','-',$card['keywords']);

                if(preg_match('/(.*): (.*)/', $card['title'], $matches)) {
                    $card['title'] = $matches[1];
                    $card['subtitle'] = $matches[2];
                } else {
                    $card['subtitle'] = "";
                }

                if(!isset($card['cost'])) {
                    if(isset($card['advancementcost'])) $card['cost'] = $card['advancementcost'];
                    else if(isset($card['baselink'])) $card['cost'] = $card['baselink'];
                    else $card['cost'] = 0;
                }

                if(!isset($card['strength'])) {
                    if(isset($card['agendapoints'])) $card['strength'] = $card['agendapoints'];
                    else if(isset($card['trash'])) $card['strength'] = $card['trash'];
                    else if(isset($card['influencelimit'])) $card['strength'] = $card['influencelimit'];
                    else if($card['type_code'] == "program") $card['strength'] = '-';
                    else $card['strength'] = '';
                }

                if(!isset($card['memoryunits'])) {
                    if(isset($card['minimumdecksize'])) $card['memoryunits'] = $card['minimumdecksize'];
                    else $card['memoryunits'] = '';
                }

                if(!isset($card['flavor'])) {
                    $card['flavor'] = '';
                }

                if($card['gang'] == "Weyland Consortium") {
                    $card['gang'] = "The Weyland Consortium";
                }

                $card['text'] = str_replace("<strong>", '', $card['text']);
                $card['text'] = str_replace("</strong>", '', $card['text']);
                $card['text'] = str_replace("<sup>", '', $card['text']);
                $card['text'] = str_replace("</sup>", '', $card['text']);
                $card['text'] = str_replace("&ndash;", ' -', $card['text']);
                $card['text'] = htmlspecialchars($card['text'], ENT_QUOTES | ENT_XML1);
                $card['text'] = str_replace("\r", '&#xD;', $card['text']);
                $card['text'] = str_replace("\n", '&#xA;', $card['text']);

                $card['flavor'] = htmlspecialchars($card['flavor'], ENT_QUOTES | ENT_XML1);
                $card['flavor'] = str_replace("\r", '&#xD;', $card['flavor']);
                $card['flavor'] = str_replace("\n", '&#xA;', $card['flavor']);

                $cardsxml[] = $card;

            }

            $response->headers->set('Content-Type', 'application/xml');
            $response->setContent($this->renderView('apiset.xml.twig', [
                "name" => $pack->getName(),
                "cards" => $cardsxml,
            ]));
*/
        } elseif ($format == 'xlsx') {
            $columns = [
                "code" => "Code",
                "pack" => "Pack",
                "number" => "Number",
                "title" => "Name",
                "cost" => "Cost",
                "type" => "Type",
                "suit" => "Suit",
                "rank" => "Rank",
                "keywords" => "Keywords",
                "text" => "Text",
                "gang" => "Gang",
                "gang_letter" => "Gang",
                "illustrator" => "Illustrator",
                "flavor" => "Flavor text",
                "quantity" => "Qty",
                "shooter" => "Shooter",
            ];

            $spreadsheet = $factory->createSpreadsheet();
            $spreadsheet->getProperties()
                ->setCreator("dtdb")
                ->setLastModifiedBy($last_modified->format('Y-m-d'))
                ->setTitle($pack->getName())
                ->setSubject($pack->getName())
                ->setDescription($pack->getName() . " Cards Description")
                ->setKeywords("doomtown reloaded " . $pack->getName());

            $worksheet = $spreadsheet->setActiveSheetIndex(0);

            $col_index = 0;
            foreach ($columns as $label) {
                $cell = $worksheet->getCellByColumnAndRow($col_index++, 1);
                $cell->setValue($label);
            }
            foreach ($cards as $row_index => $card) {
                $col_index = 0;
                foreach ($columns as $key => $label) {
                    $value = $card[$key] ?? '';
                    $cell = $worksheet->getCellByColumnAndRow($col_index++, $row_index + 2);
                    if ($key == 'code') {
                        $cell->setValueExplicit($value, DataType::TYPE_STRING);
                    } else {
                        $cell->setValue($value);
                    }
                }
            }
            $response = $factory->createStreamedResponse($spreadsheet, 'Xls');
            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment;filename=' . $pack->getName() . '.xlsx');
            $response->headers->add(['Access-Control-Allow-Origin' => '*']);
        }

        return $response;
    }

    /**
     * @Route(
     *     "/api/decklist/{decklist_id}.{_format}",
     *     name="api_decklist",
     *     format="json",
     *     methods={"GET"},
     *     requirements={
     *         "_format"="json",
     *         "decklist_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param int $longCache
     * @param string $decklist_id
     * @param Request $request
     * @return Response
     */
    public function decklistAction(EntityManagerInterface $entityManager, $longCache, $decklist_id, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }
        $dbh = $entityManager->getConnection();
        $rows = $dbh->executeQuery(
            "SELECT
            d.id,
            d.ts,
            d.name,
            d.creation,
            d.description,
            u.username
            FROM decklist d
            JOIN user u ON d.user_id = u.id
            WHERE d.id = ?
            ",
            [$decklist_id]
        )->fetchAll();

        if (empty($rows)) {
            throw new NotFoundHttpException('Wrong id');
        }

        $decklist = $rows[0];
        $decklist['id'] = intval($decklist['id']);

        $lastModified = new DateTime($decklist['ts']);
        $response->setLastModified($lastModified);
        if ($response->isNotModified($request)) {
            return $response;
        }
        unset($decklist['ts']);

        $cards = $dbh->executeQuery(
            "SELECT
            c.code card_code,
            s.quantity qty
            FROM decklistslot s
            JOIN card c ON s.card_id = c.id
            WHERE s.decklist_id = ?
            ORDER BY c.code ASC",
            [$decklist_id]
        )->fetchAll();

        $decklist['cards'] = [];
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

    /**
     * @Route(
     *     "/api/decklists/by_date/{date}.{_format}",
     *     name="api_decklists",
     *     format="json",
     *     methods={"GET"},
     *     requirements={
     *         "_format"="json",
     *         "date"="\d\d\d\d-\d\d-\d\d"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param int $longCache
     * @param string $date
     * @param Request $request
     * @return Response
     */
    public function decklistsAction(EntityManagerInterface $entityManager, $longCache, $date, Request $request)
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);
        $jsonp = $request->query->get('jsonp');
        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }

        $dbh = $entityManager->getConnection();
        $decklists = $dbh->executeQuery(
            "SELECT
            d.id,
            d.ts,
            d.name,
            d.creation,
            d.description,
            u.username
            FROM decklist d
            JOIN user u ON d.user_id = u.id
            WHERE SUBSTRING(d.creation, 1, 10) = ?
            ",
            [$date]
        )->fetchAll();

        $lastTS = null;
        foreach ($decklists as $i => $decklist) {
            $lastTS = max($lastTS, $decklist['ts']);
            unset($decklists[$i]['ts']);
        }
        $response->setLastModified(new DateTime($lastTS));
        if ($response->isNotModified($request)) {
            return $response;
        }

        foreach ($decklists as $i => $decklist) {
            $decklists[$i]['id'] = intval($decklist['id']);

            $cards = $dbh->executeQuery(
                "SELECT
                c.code card_code,
                s.quantity qty
                FROM decklistslot s
                JOIN card c ON s.card_id = c.id
                WHERE s.decklist_id = ?
                ORDER BY c.code ASC",
                [$decklists[$i]['id']]
            )->fetchAll();

            $decklists[$i]['cards'] = [];
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

    /**
     * @Route("/api/decks", name="api_decks", methods={"GET"})
     * @param Decks $decks
     * @param Request $request
     * @return Response
     */
    public function decksAction(Decks $decks, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');

        $locale = $request->query->get('_locale');
        if (isset($locale)) {
            $request->setLocale($locale);
        }

        $user = $this->getUser();

        if (! $user) {
            throw new UnauthorizedHttpException('Unauthorized Access.');
        }

        $response->setContent(json_encode($decks->getByUser($user)));
        return $response;
    }

    /**
     * @Route(
     *     "/api_oauth2/save_deck/{deck_id}",
     *     name="api_oauth2_save_deck",
     *     methods={"PUT", "POST"},
     *     requirements={
     *         "deck_id"="\d+"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param Decks $decks
     * @param $deck_id
     * @param Request $request
     * @return Response
     */
    public function saveDeckAction(EntityManagerInterface $entityManager, Decks $decks, $deck_id, Request $request)
    {
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');

        $user = $this->getUser();
        if (count($user->getDecks()) > $user->getMaxNbDecks()) {
            $response->setContent(
                json_encode([
                    'success' => false,
                    'message' => 'You have reached the maximum number of decks allowed.'
                        . ' Delete some decks or increase your reputation.',
                ])
            );
            return $response;
        }

        $name = filter_var($request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $decklist_id = filter_var($request->get('decklist_id'), FILTER_SANITIZE_NUMBER_INT);
        $description = filter_var($request->get('description'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $tags = filter_var($request->get('tags'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $content = json_decode($request->get('content'), true);
        if (! count($content)) {
            $response->setContent(json_encode(['success' => false, 'message' => 'Cannot import empty deck']));
            return $response;
        }

        if ($deck_id) {
            $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
            if ($user->getId() != $deck->getUser()->getId()) {
                $response->setContent(json_encode(['success' => false, 'message' => 'Wrong user']));
                return $response;
            }
            foreach ($deck->getSlots() as $slot) {
                $deck->removeSlot($slot);
                $entityManager->remove($slot);
            }
        } else {
            $deck = new Deck();
        }

        // $content is formatted as {card_code,qty}, expected {card_code=>qty}
        $slots = [];
        foreach ($content as $arr) {
            $slots[$arr['card_code']] = intval($arr['qty']);
        }

        $deck_id = $decks->saveDeck(
            $this->getUser(),
            $deck,
            $decklist_id,
            $name,
            $description,
            $tags,
            $slots,
            $deck_id ? $deck : null
        );

        if (isset($deck_id)) {
            $response->setContent(json_encode(['success' => true, 'message' => $decks->getById($deck_id)]));
        } else {
            $response->setContent(json_encode(['success' => false, 'message' => 'Unknown error']));
        }

        return $response;
    }

    /**
     * @Route(
     *     "/api_oauth2/publish_deck/{deck_id}",
     *     name="api_oauth2_publish_deck",
     *     methods={"GET", "POST"},
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
        $response = new Response();
        $response->setPrivate();
        $response->headers->set('Content-Type', 'application/json');

        /* @var Deck $deck */
        $deck = $entityManager->getRepository(Deck::class)->find($deck_id);
        if ($this->getUser()->getId() != $deck->getUser()->getId()) {
            $response->setContent(
                json_encode(['success' => false, 'message' => "You don't have access to this deck."])
            );
            return $response;
        }

        $analyse = $judge->analyse($deck->getCards());
        if (is_string($analyse)) {
            $response->setContent(json_encode(['success' => false, 'message' => $judge->problem($analyse)]));
            return $response;
        }

        $new_content = json_encode($deck->getContent());
        $new_signature = md5($new_content);
        $old_decklists = $entityManager->getRepository(Decklist::class)->findBy(['signature' => $new_signature]);
        foreach ($old_decklists as $decklist) {
            if (json_encode($decklist->getContent()) == $new_content) {
                $response->setContent(json_encode(['success' => false, 'message' => "That decklist already exists."]));
                return $response;
            }
        }

        $name = filter_var($request->request->get('name'), FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
        $name = substr($name, 0, 60);
        if (empty($name)) {
            $name = $deck->getName();
        }

        $rawdescription = filter_var(
            $request->request->get('description'),
            FILTER_SANITIZE_STRING,
            FILTER_FLAG_NO_ENCODE_QUOTES
        );
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
        $decklist->setCreation(new DateTime());
        $decklist->setTs(new DateTime());
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
        } elseif ($deck->getParent()) {
            $decklist->setPrecedent($deck->getParent());
        }
        $decklist->setParent($deck);

        $entityManager->persist($decklist);
        $entityManager->flush();

        $response->setContent(
            json_encode([
                    'success' => true,
                    'message' => [
                        "id" => $decklist->getId(),
                        "url" => $this->generateUrl(
                            'decklist_detail',
                            [
                                'decklist_id' => $decklist->getId(),
                                'decklist_name' => $decklist->getPrettyName()
                            ]
                        )
                    ]
                ])
        );
        return $response;
    }
}
