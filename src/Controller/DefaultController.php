<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Entity\Gang;
use App\Entity\Pack;
use App\Entity\Shooter;
use App\Entity\Type;
use App\Services\CardsData;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    /**
     * @Route(
     *     "/{_locale}/search",
     *     name="cards_search",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @param EntityManagerInterface $entityManager
     * @param CardsData $cardsData
     * @param $longCache
     * @return Response
     */
    public function searchAction(
        EntityManagerInterface $entityManager,
        CardsData $cardsData,
        $longCache
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($longCache);
        $dbh = $entityManager->getConnection();
        $list_packs = $entityManager->getRepository(Pack::class)->findBy([], ["released" => "ASC", "number" => "ASC"]);
        $packs = [];
        foreach ($list_packs as $pack) {
            $packs[] = [
                "name" => $pack->getName(),
                "code" => $pack->getCode(),
            ];
        }
        $list_cycles = $entityManager->getRepository(Cycle::class)->findBy([], ["number" => "ASC"]);
        $cycles = [];
        foreach ($list_cycles as $cycle) {
            $cycles[] = [
                "name" => $cycle->getName(),
                "code" => $cycle->getCode(),
            ];
        }
        $list_types = $entityManager->getRepository(Type::class)->findBy([], ["name" => "ASC"]);
        $types = array_map(function ($type) {
            return strtolower($type->getName());
        }, $list_types);
        $list_gangs = $entityManager->getRepository(Gang::class)->findBy([], ["id" => "ASC"]);
        $gangs = [];
        foreach ($list_gangs as $gang) {
            $gangs[] = [
                "name" => $gang->getName(),
                "code" => $gang->getCode(),
                "code1" => $gang->getCode()[0],
            ];
        }
        $gangs[] = [
            "name" => "Neutral",
            "code" => "neutral",
            "code1" => '-',
        ];
        $list_shooters = $entityManager->getRepository(Shooter::class)->findBy([], ["name" => "ASC"]);
        $shooters = array_map(function ($shooter) {
            return strtolower($shooter->getName());
        }, $list_shooters);

        $list_keywords = $dbh->executeQuery("SELECT DISTINCT c.keywords FROM card c WHERE c.keywords != ''")
            ->fetchAll();
        $keywords = [];
        foreach ($list_keywords as $keyword) {
            $subs = explode(' • ', $keyword["keywords"]);
            foreach ($subs as $sub) {
                $sub = preg_replace('/ \d+$/', '', $sub);
                $keywords[$sub] = 1;
            }
        }
        $keywords = array_keys($keywords);
        sort($keywords);
        $list_illustrators = $dbh
            ->executeQuery("SELECT DISTINCT c.illustrator FROM card c WHERE c.illustrator != '' ORDER BY c.illustrator")
            ->fetchAll();
        $illustrators = array_map(function ($elt) {
            return $elt["illustrator"];
        }, $list_illustrators);
        return $this->render(
            'Search/searchform.html.twig',
            [
                "pagetitle" => "Card Search",
                "packs" => $packs,
                "cycles" => $cycles,
                "types" => $types,
                "gangs" => $gangs,
                "shooters" => $shooters,
                "keywords" => $keywords,
                "illustrators" => $illustrators,
                "allsets" => $this->renderView('Default/allsets.html.twig', [
                    "data" => $cardsData->allsetsdata(),
                ]),
                'locales' => $this->renderView('Default/langs.html.twig'),
            ],
            $response
        );
    }

    /**
     * @Route(
     *     "/{_locale}/about",
     *     name="cards_about",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function aboutAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/about.html.twig', ["pagetitle" => "About"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/changelog",
     *     name="cards_changelog",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function changelogAction()
    {

        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/changelog.html.twig', ["pagetitle" => "Change Log"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/rules",
     *     name="cards_rules",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function rulesAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/rules.html.twig', ["pagetitle" => "Rules"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/extraRules",
     *     name="cards_extraRules",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function extraRulesAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/extraRules.html.twig', ["pagetitle" => "Additional Rules"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/structure",
     *     name="cards_structure",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function structureAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/structure.html.twig', ["pagetitle" => "Turn Structure"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/faq",
     *     name="cards_faq",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function faqAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/faq.html.twig', ["pagetitle" => "FAQ"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/floorRules",
     *     name="cards_floor",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function floorRulesAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/floorRules.html.twig', ["pagetitle" => "Tournament Rules"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/octgnGuide",
     *     name="cards_octgnGuide",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function octgnGuideAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/octgnGuide.html.twig', ["pagetitle" => "Guide to OCTGN"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/collectedRulings",
     *     name="cards_collectedRulings",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function collectedRulingsAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/collectedRulings.html.twig', ["pagetitle" => "Collected Rulings"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/oldRules",
     *     name="cards_oldRules",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function oldRulesAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/oldRules.html.twig', ["pagetitle" => "Rules Archives"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/oldFaqs",
     *     name="cards_oldFaqs",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function oldFaqsAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/oldFaqs.html.twig', ["pagetitle" => "FAQs Archives"], $response);
    }

    /**
     * @Route(
     *     "/{_locale}/apidoc",
     *     name="cards_api",
     *     locale="en",
     *     methods={"GET"},
     *     requirements={
     *         "_locale"="en|fr|de|es|it|pl",
     *     }
     * )
     * @return Response
     */
    public function apidocAction()
    {
        $response = new Response();
        $response->setPrivate();
        return $this->render('Default/apidoc.html.twig', ["pagetitle" => "API documentation"], $response);
    }
}
