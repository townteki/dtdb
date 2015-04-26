<?php

namespace Dtdb\CardsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dtdb\CardsBundle\Entity\Card;
use Dtdb\CardsBundle\Entity\Pack;
use Dtdb\CardsBundle\Entity\Cycle;

class DefaultController extends Controller
{
	public function searchAction()
	{
	    $response = new Response();
	    $response->setPublic();
	    $response->setMaxAge($this->container->getParameter('long_cache'));
	     
		$dbh = $this->get('doctrine')->getConnection();
	
		$list_packs = $this->getDoctrine()->getRepository('DtdbCardsBundle:Pack')->findBy(array(), array("released" => "ASC", "number" => "ASC"));
		$packs = array();
		foreach($list_packs as $pack) {
			$packs[] = array(
					"name" => $pack->getName($this->getRequest()->getLocale()),
					"code" => $pack->getCode(),
			);
		}
	
		$list_cycles = $this->getDoctrine()->getRepository('DtdbCardsBundle:Cycle')->findBy(array(), array("number" => "ASC"));
		$cycles = array();
		foreach($list_cycles as $cycle) {
			$cycles[] = array(
					"name" => $cycle->getName($this->getRequest()->getLocale()),
					"code" => $cycle->getCode(),
			);
		}
	
		$list_types = $this->getDoctrine()->getRepository('DtdbCardsBundle:Type')->findBy(array(), array("name" => "ASC"));
		$types = array_map(function ($type) {
			return strtolower($type->getName());
		}, $list_types);

		$list_shooters = $this->getDoctrine()->getRepository('DtdbCardsBundle:shooter')->findBy(array(), array("name" => "ASC"));
		$shooters = array_map(function ($shooter) {
		    return strtolower($shooter->getName());
		}, $list_shooters);
    
		$list_keywords = $dbh->executeQuery("SELECT DISTINCT c.keywords FROM card c WHERE c.keywords != ''")->fetchAll();
		$keywords = array();
		foreach($list_keywords as $keyword) {
			$subs = explode(' • ', $keyword["keywords"]);
			foreach($subs as $sub) {
			    $sub = preg_replace('/ \d+$/', '', $sub);
				$keywords[$sub] = 1;
			}
		}
		$keywords = array_keys($keywords);
		sort($keywords);
	
		$list_illustrators = $dbh->executeQuery("SELECT DISTINCT c.illustrator FROM card c WHERE c.illustrator != '' ORDER BY c.illustrator")->fetchAll();
		$illustrators = array_map(function ($elt) {
			return $elt["illustrator"];
		}, $list_illustrators);
	
		return $this->render('DtdbCardsBundle:Search:searchform.html.twig', array(
		        "pagetitle" => "Card Search",
				"packs" => $packs,
				"cycles" => $cycles,
				"types" => $types,
		        "shooters" => $shooters,
				"keywords" => $keywords,
				"illustrators" => $illustrators,
				"allsets" => $this->renderView('DtdbCardsBundle:Default:allsets.html.twig', array(
                    "data" => $this->get('cards_data')->allsetsdata(),
		        )),
				'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
		), $response);
	}
	
	function aboutAction()
	{
		
		$response = new Response();
		$response->setPrivate();
		
		return $this->render('DtdbCardsBundle:Default:about.html.twig', array(
		        "pagetitle" => "About",
		), $response);
	}
	
	function changelogAction()
	{
		
		$response = new Response();
		$response->setPrivate();
		
		return $this->render('DtdbCardsBundle:Default:changelog.html.twig', array(
		        "pagetitle" => "Change Log",
		), $response);
	}

	function apidocAction()
	{
		
		$response = new Response();
		$response->setPrivate();
		
		return $this->render('DtdbCardsBundle:Default:apidoc.html.twig', array(
		        "pagetitle" => "API documentation",
		), $response);
	}
}
