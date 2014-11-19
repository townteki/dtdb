<?php
namespace Dtdb\BuilderBundle\Controller;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IndexController extends Controller
{
    public function indexAction(Request $request)
    {

        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('short_cache'));
        
        // decklist of the week
        $decklist = $this->get('highlight')->get();
        
        // recent decklists
        $decklists_recent = $this->get('decklists')->recent(0, 10)['decklists'];
        
        // recent reviews
        $reviews_recent = $this->get('reviews')->recent(0, 5)['reviews'];
        $texts = $this->get('texts');
        foreach($reviews_recent as $i => $review) {
            $reviews_recent[$i]['rawtext'] = $texts->truncate(strip_tags($texts->markdown($review['rawtext'])),200);
        }
        
        return $this->render('DtdbBuilderBundle:Default:index.html.twig',
                array(
                        'pagetitle' => "Doomtown Cards and Deckbuilder",
                        'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
                        'decklists' => $decklists_recent,
                        'decklist' => $decklist,
                        'reviews' => $reviews_recent,
                        'url' => $this->getRequest()
                        ->getRequestUri()
                ), $response);
        
        
    }
}
