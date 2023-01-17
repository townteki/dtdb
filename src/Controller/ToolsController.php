<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ToolsController extends AbstractController
{
    /**
     * @Route("/tools/demo", name="tools_demo", methods={"GET"})
     * @return Response
     */
    public function demoAction()
    {
        return $this->render('Tools/demo.html.twig');
    }
}
