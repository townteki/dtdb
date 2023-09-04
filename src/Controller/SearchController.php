<?php

namespace App\Controller;

use App\Entity\Card;
use App\Entity\Cycle;
use App\Entity\Pack;
use App\Services\CardsData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class SearchController extends AbstractController
{
    /**
     * @Route(
     *     "/{_locale}/card/{card_code}",
     *     name="cards_zoom",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "card_code"="\d+",
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $card_code
     * @param Request $request
     * @return Response
     */
    public function zoomAction(
        EntityManagerInterface $entityManager,
        $card_code,
        Request $request
    ) {
        $card = $entityManager->getRepository(Card::class)->findOneBy(["code" => $card_code]);
        if (!$card) {
            throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
        }
        $meta = $card->getTitle()
            . ", a "
            . $card->getType()->getName()
            . " card for Doomtown from the set "
            . $card->getPack()->getName()
            . ".";
        return $this->forward(
            'App\Controller\SearchController::displayAction',
            [
                'q' => $card->getCode(),
                'view' => 'card',
                'sort' => 'set',
                'title' => $card->getTitle(),
                'meta' => $meta,
                'locale' => $request->getLocale(),
                'locales' => $this->renderView('Default/langs.html.twig'),
            ]
        );
    }

    /**
     * @Route(
     *     "/{_locale}/set/{pack_code}/{view}/{sort}/{page}",
     *     name="cards_list",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={
     *         "view"="list",
     *         "sort"="set",
     *         "page"=1
     *     },
     *     requirements={
     *         "page"="\d+",
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $pack_code
     * @param $view
     * @param $sort
     * @param $page
     * @param Request $request
     * @return Response
     */
    public function listAction(
        EntityManagerInterface $entityManager,
        $pack_code,
        $view,
        $sort,
        $page,
        Request $request
    ) {
        $pack = $entityManager->getRepository(Pack::class)->findOneBy(["code" => $pack_code]);
        if (!$pack) {
            throw $this->createNotFoundException('This pack does not exist');
        }
        $meta = $pack->getName() . ", a set of cards for Doomtown"
                . ($pack->getReleased() ? " published on " . $pack->getReleased()->format('Y/m/d') : "")
                . " by AEG.";
        return $this->forward(
            'App\Controller\SearchController::displayAction',
            [
                'q' => 'e:' . $pack_code,
                'view' => $view,
                'sort' => $sort,
                'page' => $page,
                'title' => $pack->getName(),
                'meta' => $meta,
                'locale' => $request->getLocale(),
                'locales' => $this->renderView('Default/langs.html.twig'),
            ],
        );
    }

    /**
     * @Route(
     *     "/{_locale}/cycle/{cycle_code}/{view}/{sort}",
     *     name="cards_cycle",
     *     locale="en",
     *     methods={"GET"},
     *     defaults={
     *         "view"="list",
     *         "sort"="gang",
     *     },
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl"
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param $cycle_code
     * @param $view
     * @param $sort
     * @param Request $request
     * @return Response
     */
    public function cycleAction(
        EntityManagerInterface $entityManager,
        $cycle_code,
        $view,
        $sort,
        Request $request
    ) {
        $cycle = $entityManager->getRepository(Cycle::class)->findOneBy(["code" => $cycle_code]);
        if (!$cycle) {
            throw $this->createNotFoundException('This cycle does not exist');
        }
        $meta = $cycle->getName() . ", a cycle of datapack for Doomtown published by AEG.";
        return $this->forward(
            'App\Controller\SearchController::displayAction',
            [
                'q' => 'c:' . $cycle->getNumber(),
                'view' => $view,
                'sort' => $sort,
                'title' => $cycle->getName(),
                'meta' => $meta,
                'locale' => $request->getLocale(),
                'locales' => $this->renderView('Default/langs.html.twig'),
            ],
        );
    }

    /**
     * @Route(
     *     "/process/",
     *     name="cards_processSearchForm",
     *     methods={"GET"}
     * )
     * @param Request $request
     * @return RedirectResponse
     */
    public function processAction(Request $request)
    {
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        $locale = $request->query->get('_locale') ?: $request->getLocale();

        $operators = [":","!","<",">"];

        $params = [];
        if ($request->query->get('q') != "") {
            $params[] = $request->query->get('q');
        }
        $keys = str_split("kxrvupbicfgtaes");
        foreach ($keys as $key) {
            $val = $request->query->get($key);
            if (isset($val) && $val != "") {
                if (is_array($val)) {
                    if ($key == "g" && count($val) == 7) {
                        continue;
                    }
                    $params[] = $key . ":" . implode("|", array_map(function ($s) {
                        return strstr($s, " ") !== false ? "\"$s\"" : $s;
                    }, $val));
                } else {
                    if (strstr($val, " ") != false) {
                        $val = "\"$val\"";
                    }
                    $op = $request->query->get($key . "o");
                    if (!in_array($op, $operators)) {
                        $op = ":";
                    }
                    if ($key == "d") {
                        $op = "";
                    }
                    $params[] = "$key$op$val";
                }
            }
        }
        $find = ['q' => implode(" ", $params)];
        if ($sort != "name") {
            $find['sort'] = $sort;
        }
        if ($view != "list") {
            $find['view'] = $view;
        }
        if ($locale != "en") {
            $find['_locale'] = $locale;
        }
        return $this->redirect($this->generateUrl('cards_find') . '?' . http_build_query($find));
    }

    /**
     * @Route(
     *     "/find/",
     *     name="cards_find",
     *     methods={"GET"}
     * )
     * @param CardsData $cardsData
     * @param RouterInterface $router
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function findAction(
        CardsData $cardsData,
        RouterInterface $router,
        Request $request
    ) {
        $q = $request->query->get('q');
        $page = $request->query->get('page') ?: 1;
        $view = $request->query->get('view') ?: 'list';
        $sort = $request->query->get('sort') ?: 'name';
        $locale = $request->query->get('_locale') ?: 'en';

        $request->setLocale($locale);

        // we may be able to redirect to a better url if the search is on a single set
        $conditions = $cardsData->syntax($q);
        if (
            count($conditions) == 1
            && count($conditions[0]) == 3
            && $conditions[0][1] == ":"
            && $conditions[0][0] == "e"
        ) {
            $url = $router->generate(
                'cards_list',
                [
                    'pack_code' => $conditions[0][2],
                    'view' => $view,
                    'sort' => $sort,
                    '_locale' => $request->getLocale(),
                    'page' => $page,
                ],
            );
            return $this->redirect($url);
        }

        return $this->forward(
            'App\Controller\SearchController::displayAction',
            [
                'q' => $q,
                'view' => $view,
                'sort' => $sort,
                'page' => $page,
                'locale' => $locale,
                'locales' => $this->renderView('Default/langs.html.twig'),
            ],
        );
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param CardsData $cardsData
     * @param Request $request
     * @param $shortCache
     * @param $q
     * @param $sort
     * @param $view
     * @param $page
     * @param $title
     * @param $meta
     * @param $locale
     * @param $locales
     * @return Response
     */
    public function displayAction(
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        CardsData $cardsData,
        Request $request,
        $shortCache,
        $q,
        $sort,
        $view = "card",
        $page = 1,
        $title = "",
        $meta = "",
        $locale = null,
        $locales = null
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        static $availability = [];

        if (empty($locale)) {
            $locale = $request->getLocale();
        }
        $request->setLocale($locale);

        $cards = [];
        $first = 0;
        $last = 0;
        $pagination = '';

        $pagesizes = [
            'list' => 200,
            'spoiler' => 200,
            'card' => 21,
            'scan' => 21,
            'short' => 1000,
            'zoom' => 1,
        ];

        if (!array_key_exists($view, $pagesizes)) {
            $view = 'list';
        }

        $conditions = $cardsData->syntax($q);

        $cardsData->validateConditions($conditions);

        // reconstruction de la bonne chaine de recherche pour affichage
        $q = $cardsData->buildQueryFromConditions($conditions);
        if ($q && $rows = $cardsData->get_search_rows($conditions, $sort)) {
            if (count($rows) == 1) {
                $view = 'zoom';
            }

            if ($title == "") {
                $title = $this->findATitle($entityManager, $conditions);
            }


            // calcul de la pagination
            $nb_per_page = $pagesizes[$view];
            $first = $nb_per_page * ($page - 1);
            if ($first > count($rows)) {
                $page = 1;
                $first = 0;
            }
            $last = $first + $nb_per_page;

            // data à passer à la view
            for ($rowindex = $first; $rowindex < $last && $rowindex < count($rows); $rowindex++) {
                $card = $rows[$rowindex];
                $pack = $card->getPack();
                $cardinfo = $cardsData->getCardInfo($card, false);
                if (empty($availability[$pack->getCode()])) {
                    $availability[$pack->getCode()] = false;
                    if ($pack->getReleased() && $pack->getReleased() <= new \DateTime()) {
                        $availability[$pack->getCode()] = true;
                    }
                }
                $cardinfo['available'] = $availability[$pack->getCode()];
                if ($view == "card" || $view == "zoom") {
                    $cardinfo['alternatives'] = $cardsData->getCardAlternatives($card);
                }
                if ($view == "zoom") {
                    $cardinfo['reviews'] = $cardsData->get_reviews($card);
                }
                $cards[] = $cardinfo;
            }

            $first += 1;

            // si on a des cartes on affiche une bande de navigation/pagination
            if (count($rows)) {
                if (count($rows) == 1) {
                    $pagination = $this->setnavigation($router, $entityManager, $request, $card, $q, $view, $sort);
                } else {
                    $pagination = $this->pagination(
                        $router,
                        $request,
                        $nb_per_page,
                        count($rows),
                        $first,
                        $q,
                        $view,
                        $sort
                    );
                }
            }

            // si on est en vue "short" on casse la liste par tri
            if (count($cards) && $view == "short") {
                $sortfields = [
                    'set' => 'pack',
                    'name' => 'title',
                    'gang' => 'gangs',
                    'type' => 'type',
                    'cost' => 'cost',
                    'rank' => 'rank',
                ];

                $brokenlist = [];
                for ($i = 0; $i < count($cards); $i++) {
                    switch ($sort) {
                        case 'gang':
                            $vals = $cards[$i]['gangs'];
                            foreach ($vals as $val) {
                                if (!isset($brokenlist[$val])) {
                                    $brokenlist[$val] = [];
                                }
                                array_push($brokenlist[$val], $cards[$i]);
                            }
                            break;
                        default:
                            $val = $cards[$i][$sortfields[$sort]];
                            if ($sort == "name") {
                                $val = substr($val, 0, 1);
                            }
                            if (!isset($brokenlist[$val])) {
                                $brokenlist[$val] = [];
                            }
                            array_push($brokenlist[$val], $cards[$i]);
                    }
                }
                $cards = $brokenlist;
            }
        }

        $searchbar = $this->renderView('Search/searchbar.html.twig', [
            "q" => $q,
            "view" => $view,
            "sort" => $sort,
        ]);

        if (empty($title)) {
            $title = $q;
        }

        // attention si $s="short", $cards est un tableau à 2 niveaux au lieu de 1 seul
        return $this->render(
            'Search/display-' . $view . '.html.twig',
            [
                "view" => $view,
                "sort" => $sort,
                "cards" => $cards,
                "first" => $first,
                "last" => $last,
                "searchbar" => $searchbar,
                "pagination" => $pagination,
                "pagetitle" => $title,
                "metadescription" => $meta,
                "locales" => $locales,
            ],
            $response
        );
    }

    /**
     * @param RouterInterface $router
     * @param EntityManagerInterface $entityManager
     * @param Request $request
     * @param $card
     * @param $q
     * @param $view
     * @param $sort
     * @return string
     */
    protected function setnavigation(
        RouterInterface $router,
        EntityManagerInterface $entityManager,
        Request $request,
        $card,
        $q,
        $view,
        $sort
    ) {
        $locale = $request->getLocale();
        $repo = $entityManager->getRepository(Card::class);
        $prev = $repo->findOneBy(["pack" => $card->getPack(), "number" => $card->getNumber() - 1]);
        $next = $repo->findOneBy(["pack" => $card->getPack(), "number" => $card->getNumber() + 1]);
        return $this->renderView(
            'Search/setnavigation.html.twig',
            [
                "prevtitle" => $prev ? $prev->getTitle($locale) : "",
                "prevhref" => $prev
                        ? $router->generate(
                            'cards_zoom',
                            [
                                'card_code' => $prev->getCode(),
                                "_locale" => $locale
                            ],
                        )
                        : "",
                "nexttitle" => $next ? $next->getTitle($locale) : "",
                "nexthref" => $next
                        ? $router->generate(
                            'cards_zoom',
                            [
                                'card_code' => $next->getCode(),
                                "_locale" => $locale
                            ],
                        )
                        : "",
                "settitle" => $card->getPack()->getName(),
                "sethref" => $router->generate(
                    'cards_list',
                    [
                        'pack_code' => $card->getPack()->getCode(),
                        "_locale" => $locale,
                    ],
                ),
                "_locale" => $locale,
            ],
        );
    }

    /**
     * @param RouterInterface $router
     * @param Request $request
     * @param $q
     * @param $v
     * @param $s
     * @param $ps
     * @param $pi
     * @param $total
     * @return string
     */
    protected function paginationItem(RouterInterface $router, Request $request, $q, $v, $s, $ps, $pi, $total)
    {
        $locale = $request->getLocale();
        return $this->renderView(
            'Search/paginationitem.html.twig',
            [
                "href" => $q == null
                    ? ""
                    : $router->generate(
                        'cards_find',
                        [
                            'q' => $q,
                            'view' => $v,
                            'sort' => $s,
                            'page' => $pi,
                            '_locale' => $locale,
                        ],
                    ),
                "ps" => $ps,
                "pi" => $pi,
                "s" => $ps * ($pi - 1) + 1,
                "e" => min($ps * $pi, $total),
            ],
        );
    }

    /**
     * @param RouterInterface $router
     * @param Request $request
     * @param $pagesize
     * @param $total
     * @param $current
     * @param $q
     * @param $view
     * @param $sort
     * @return string
     */
    protected function pagination(
        RouterInterface $router,
        Request $request,
        $pagesize,
        $total,
        $current,
        $q,
        $view,
        $sort
    ) {
        if ($total < $pagesize) {
            $pagesize = $total;
        }

        $pagecount = ceil($total / $pagesize);
        $pageindex = ceil($current / $pagesize); #1-based

        $startofpage = ($pageindex - 1) * $pagesize + 1;
        $endofpage = $startofpage + $pagesize;

        $first = "";
        if ($pageindex > 2) {
            $first = $this->paginationItem($router, $request, $q, $view, $sort, $pagesize, 1, $total);
        }

        $prev = "";
        if ($pageindex > 1) {
            $prev = $this->paginationItem($router, $request, $q, $view, $sort, $pagesize, $pageindex - 1, $total);
        }

        $current = $this->paginationItem($router, $request, null, $view, $sort, $pagesize, $pageindex, $total);

        $next = "";
        if ($pageindex < $pagecount) {
            $next = $this->paginationItem($router, $request, $q, $view, $sort, $pagesize, $pageindex + 1, $total);
        }

        $last = "";
        if ($pageindex < $pagecount - 1) {
            $last = $this->paginationItem($router, $request, $q, $view, $sort, $pagesize, $pagecount, $total);
        }

        return $this->renderView('Search/pagination.html.twig', [
            "first" => $first,
            "prev" => $prev,
            "current" => $current,
            "next" => $next,
            "last" => $last,
            "count" => $total,
            "ellipsisbefore" => $pageindex > 3,
            "ellipsisafter" => $pageindex < $pagecount - 2,
        ]);
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param array $conditions
     * @return string
     */
    protected function findATitle(EntityManagerInterface $entityManager, array $conditions)
    {
        $title = "";
        if (count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
            if ($conditions[0][0] == "e") {
                $pack = $entityManager->getRepository(Pack::class)->findOneBy(["code" => $conditions[0][2]]);
                if ($pack) {
                    $title = $pack->getName();
                }
            }
            if ($conditions[0][0] == "c") {
                $cycle = $entityManager->getRepository(Cycle::class)->findOneBy(["code" => $conditions[0][2]]);
                if ($cycle) {
                    $title = $cycle->getName();
                }
            }
        }
        return $title;
    }
}
