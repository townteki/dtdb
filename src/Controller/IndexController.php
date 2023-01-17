<?php

namespace App\Controller;

use App\Services\Decklists;
use App\Services\Highlight;
use App\Services\Reviews;
use App\Services\Texts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     * @param int $shortCache
     * @param Highlight $highlight
     * @param Decklists $decklists
     * @param Reviews $reviews
     * @param Texts $texts
     * @param Request $request
     * @return Response
     */
    public function indexAction(
        $shortCache,
        Highlight $highlight,
        Decklists $decklists,
        Reviews $reviews,
        Texts $texts,
        Request $request
    ) {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($shortCache);

        // decklist of the week
        $decklist = $highlight->get();

        // recent decklists
        $decklists_recent = $decklists->recent(0, 10)['decklists'];

        // recent reviews
        $reviews_recent = $reviews->recent(0, 5)['reviews'];
        foreach ($reviews_recent as $i => $review) {
            $reviews_recent[$i]['rawtext'] = $texts->truncate(strip_tags($texts->markdown($review['rawtext'])), 200);
        }

        return $this->render(
            'Default/index.html.twig',
            [
                    'pagetitle' => "Doomtown Cards and Deckbuilder",
                    'locales' => $this->renderView('Default/langs.html.twig'),
                    'decklists' => $decklists_recent,
                    'decklist' => $decklist,
                    'reviews' => $reviews_recent,
                    'url' => $request->getRequestUri()
            ],
            $response
        );
    }
}
