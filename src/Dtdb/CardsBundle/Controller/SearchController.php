<?php

namespace Dtdb\CardsBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dtdb\CardsBundle\Controller\DefaultController;
use \Michelf\Markdown;
use Dtdb\CardsBundle\DtdbCardsBundle;
use Symfony\Component\HttpFoundation\Request;

class SearchController extends Controller
{
	public function zoomAction($card_code, Request $request)
	{
		$mode = $request->query->get('mode');
		$card = $this->getDoctrine()->getRepository('DtdbCardsBundle:Card')->findOneBy(array("code" => $card_code));
		if(!$card) throw $this->createNotFoundException('Sorry, this card is not in the database (yet?)');
		$meta = $card->getTitle().", a ".($card->getGang() ? $card->getGang()->getName() : '')." ".$card->getType()->getName()." card for Doomtown from the set ".$card->getPack()->getName()." published by AEG.";
		return $this->forward(
			'DtdbCardsBundle:Search:display',
			array(
				'q' => $card->getCode(),
				'view' => 'card',
				'sort' => 'set',
				'title' => $card->getTitle(),
				'mode' => $mode,
				'meta' => $meta,
				'locale' => $this->getRequest()->getLocale(),
				'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
			)
		);
	}
	
	public function listAction($pack_code, $view, $sort, $page, Request $request)
	{
		$mode = $request->query->get('mode');
		$pack = $this->getDoctrine()->getRepository('DtdbCardsBundle:Pack')->findOneBy(array("code" => $pack_code));
		if(!$pack) throw $this->createNotFoundException('This pack does not exist');
		$meta = $pack->getName($this->getRequest()->getLocale()).", a set of cards for Doomtown"
				.($pack->getReleased() ? " published on ".$pack->getReleased()->format('Y/m/d') : "")
				." by AEG.";
		return $this->forward(
			'DtdbCardsBundle:Search:display',
			array(
				'q' => 'e:'.$pack_code,
				'view' => $view,
				'sort' => $sort,
			    'page' => $page,
				'title' => $pack->getName($this->getRequest()->getLocale()),
				'mode' => $mode,
				'meta' => $meta,
				'locale' => $this->getRequest()->getLocale(),
				'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
			)
		);
	}

	public function cycleAction($cycle_code, $view, $sort, Request $request)
	{
		$mode = $request->query->get('mode');
		$cycle = $this->getDoctrine()->getRepository('DtdbCardsBundle:Cycle')->findOneBy(array("code" => $cycle_code));
		if(!$cycle) throw $this->createNotFoundException('This cycle does not exist');
		$meta = $cycle->getName($this->getRequest()->getLocale()).", a cycle of datapack for Doomtown published by AEG.";
		return $this->forward(
			'DtdbCardsBundle:Search:display',
			array(
				'q' => 'c:'.$cycle->getNumber(),
				'view' => $view,
				'sort' => $sort,
			    'title' => $cycle->getName($this->getRequest()->getLocale()),
				'mode' => $mode,
				'meta' => $meta,
				'locale' => $this->getRequest()->getLocale(),
				'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
			)
		);
	}
	
	// target of the search form
	public function processAction(Request $request)
	{
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';
		$locale = $request->query->get('_locale') ?: $this->getRequest()->getLocale();
		
		$operators = array(":","!","<",">");
		
		$params = array();
		if($request->query->get('q') != "") {
			$params[] = $request->query->get('q');
		}
		$keys = str_split("kxrvupbicfgtaes");
		foreach($keys as $key) {
			$val = $request->query->get($key);
			if(isset($val) && $val != "") {
				if(is_array($val)) {
					if($key == "g" && count($val) == 5) continue;
					$params[] = $key.":".implode("|", array_map(function ($s) { return strstr($s, " ") !== FALSE ? "\"$s\"" : $s; }, $val));
				} else {
					if(strstr($val, " ") != FALSE) {
						$val = "\"$val\"";
					}
					$op = $request->query->get($key."o");
					if(!in_array($op, $operators)) {
						$op = ":";
					}
					if($key == "d") {
						$op = "";
					}
					$params[] = "$key$op$val";
				}
			}
		}
		$find = array('q' => implode(" ",$params));
		if($sort != "name") $find['sort'] = $sort;
		if($view != "list") $find['view'] = $view;
		if($locale != "en") $find['_locale'] = $locale;
		return $this->redirect($this->generateUrl('cards_find').'?'.http_build_query($find));
	}

	// target of the search input
	public function findAction(Request $request)
	{
		$request  = $this->getRequest();
		$q = $request->query->get('q');
		$page = $request->query->get('page') ?: 1;
		$view = $request->query->get('view') ?: 'list';
		$sort = $request->query->get('sort') ?: 'name';
		$locale = $request->query->get('_locale') ?: 'en';
		$mode = $request->query->get('mode');
		
		$request->setLocale($locale);

		// we may be able to redirect to a better url if the search is on a single set
		$conditions = $this->get('cards_data')->syntax($q);
		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":" && $conditions[0][0] == "e") {
	        $url = $this->get('router')->generate('cards_list', array('pack_code' => $conditions[0][2], 'view' => $view, 'sort' => $sort, '_locale' => $request->getLocale(), 'page' => $page));
	        return $this->redirect($url);
	    }
		
		return $this->forward(
			'DtdbCardsBundle:Search:display',
			array(
				'q' => $q,
				'view' => $view,
				'sort' => $sort,
				'page' => $page,
				'mode' => $mode,
				'locale' => $locale,
				'locales' => $this->renderView('DtdbCardsBundle:Default:langs.html.twig'),
			)
		);
	}
	
	private function findATitle($conditions) {
		$title = "";
		if(count($conditions) == 1 && count($conditions[0]) == 3 && $conditions[0][1] == ":") {
			if($conditions[0][0] == "e") {
				$pack = $this->getDoctrine()->getRepository('DtdbCardsBundle:Pack')->findOneBy(array("code" => $conditions[0][2]));
				if($pack) $title = $pack->getName($this->getRequest()->getLocale());
			}
			if($conditions[0][0] == "c") {
				$cycle = $this->getDoctrine()->getRepository('DtdbCardsBundle:Cycle')->findOneBy(array("code" => $conditions[0][2]));
				if($cycle) $title = $cycle->getName($this->getRequest()->getLocale());
			}
		}
		return $title;
	}
	
	public function displayAction($q, $view="card", $sort, $page=1, $title="", $mode="full", $meta="", $locale=null, $locales=null)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		
	    static $availability = array();

		if(empty($locale)) $locale = $this->getRequest()->getLocale();
		$this->getRequest()->setLocale($locale);
		
		$cards = array();
		$first = 0;
		$last = 0;
		$pagination = '';
		
		$pagesizes = array(
			'list' => 200,
			'spoiler' => 200,
			'card' => 21,
			'scan' => 21,
			'short' => 1000,
		);
		
		if(!array_key_exists($view, $pagesizes))
		{
			$view = 'list';
		}
		
		$conditions = $this->get('cards_data')->syntax($q);

		$this->get('cards_data')->validateConditions($conditions);

		// reconstruction de la bonne chaine de recherche pour affichage
		$q = $this->get('cards_data')->buildQueryFromConditions($conditions);
		if($q && $rows = $this->get('cards_data')->get_search_rows($conditions, $sort))
		{
			if(count($rows) == 1)
			{
				$view = "card";
			}
			
			if($title == "") $title = $this->findATitle($conditions);
			
			
			// calcul de la pagination
			$nb_per_page = $pagesizes[$view];
			$first = $nb_per_page * ($page - 1);
			if($first > count($rows)) {
				$page = 1;
				$first = 0;
			}
			$last = $first + $nb_per_page;
			
			// data à passer à la view
			for($rowindex = $first; $rowindex < $last && $rowindex < count($rows); $rowindex++) {
				$card = $rows[$rowindex];
				$pack = $card->getPack();
				$cardinfo = $this->get('cards_data')->getCardInfo($card, false);
				if(empty($availability[$pack->getCode()])) {
					$availability[$pack->getCode()] = false;
					if($pack->getReleased() && $pack->getReleased() <= new \DateTime()) $availability[$pack->getCode()] = true;
				}
				$cardinfo['available'] = $availability[$pack->getCode()];
				if($view == "spoiler" || $view == "card") {
					$cardinfo['text'] = implode(array_map(function ($l) { return "<p>$l</p>"; }, explode("\r\n", $cardinfo['text'])));
					if($view == "card") {
						$cardinfo['alternatives'] = $this->get('cards_data')->getCardAlternatives($card);
					}
				}
				$cards[] = $cardinfo;
			}

			$first += 1;

			// si on a des cartes on affiche une bande de navigation/pagination
			if(count($rows)) {
				if(count($rows) == 1) {
					$pagination = $this->setnavigation($card, $q, $view, $sort);
				} else {
					$pagination = $this->pagination($nb_per_page, count($rows), $first, $q, $view, $sort);
				}
			}
			
			// si on est en vue "short" on casse la liste par tri
			if(count($cards) && $view == "short") {
				
				$sortfields = array(
					'set' => 'pack',
					'name' => 'title',
					'gang' => 'gang',
					'type' => 'type',
					'cost' => 'cost',
					'strength' => 'strength',
				);
				
				$brokenlist = array();
				for($i=0; $i<count($cards); $i++) {
					$val = $cards[$i][$sortfields[$sort]];
					if($sort == "name") $val = substr($val, 0, 1);
					if(!isset($brokenlist[$val])) $brokenlist[$val] = array();
					array_push($brokenlist[$val], $cards[$i]);
				}
				$cards = $brokenlist;
			}
		}
		
		$searchbar = $this->renderView('DtdbCardsBundle:Search:searchbar.html.twig', array(
			"q" => $q,
			"view" => $view,
			"sort" => $sort,
		));
		
		if(empty($title)) {
			$title = $q;
		}

		$mode_templates = array(
			"full" => 'DtdbCardsBundle::main.html.twig',
			"fragment" => 'DtdbCardsBundle::fragment.html.twig',
			"embed" => 'DtdbCardsBundle::embed.html.twig',
		);
		if(empty($mode_templates[$mode])) {
			$mode = 'full';
		}

		$response->headers->set('RequestUri', preg_replace('/[\&\?]mode=fragment/', '', $this->getRequest()->getRequestUri()));
		$response->headers->set('PageTitle', rawurlencode($title));
		$response->headers->set('SearchString', rawurlencode($q));
		
		// attention si $s="short", $cards est un tableau à 2 niveaux au lieu de 1 seul
		return $this->render('DtdbCardsBundle:Search:display-'.$view.'.html.twig', array(
			"view" => $view,
			"sort" => $sort,
			"cards" => $cards,
			"first"=> $first,
			"last" => $last,
			"searchbar" => $searchbar,
			"pagination" => $pagination,
			"pagetitle" => $title,
			"mode" => $mode,
			"metadescription" => $meta,
			"display_mode_template" => $mode_templates[$mode],
			"locales" => $locales,
		), $response);
	}
	
	public function setnavigation($card, $q, $view, $sort)
	{
		$locale = $this->getRequest()->getLocale();
		$em = $this->getDoctrine();
		$prev = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array("pack" => $card->getPack(), "number" => $card->getNumber()-1));
		$next = $em->getRepository('DtdbCardsBundle:Card')->findOneBy(array("pack" => $card->getPack(), "number" => $card->getNumber()+1));
		return $this->renderView('DtdbCardsBundle:Search:setnavigation.html.twig', array(
			"prevtitle" => $prev ? $prev->getTitle($locale) : "",
			"prevhref" => $prev ? $this->get('router')->generate('cards_zoom', array('card_code' => $prev->getCode(), "_locale" => $locale)) : "",
			"nexttitle" => $next ? $next->getTitle($locale) : "",
			"nexthref" => $next ? $this->get('router')->generate('cards_zoom', array('card_code' => $next->getCode(), "_locale" => $locale)) : "",
			"settitle" => $card->getPack()->getName(),
			"sethref" => $this->get('router')->generate('cards_list', array('pack_code' => $card->getPack()->getCode(), "_locale" => $locale)),
			"_locale" => $locale,
		));
	}

	public function paginationItem($q = null, $v, $s, $ps, $pi, $total)
	{
		$locale = $this->getRequest()->getLocale();
		return $this->renderView('DtdbCardsBundle:Search:paginationitem.html.twig', array(
			"href" => $q == null ? "" : $this->get('router')->generate('cards_find', array('q' => $q, 'view' => $v, 'sort' => $s, 'page' => $pi, '_locale' => $locale)),
			"ps" => $ps,
			"pi" => $pi,
			"s" => $ps*($pi-1)+1,
			"e" => min($ps*$pi, $total),
		));
	}
	
	public function pagination($pagesize, $total, $current, $q, $view, $sort)
	{
		if($total < $pagesize) {
			$pagesize = $total;
		}
	
		$pagecount = ceil($total / $pagesize);
		$pageindex = ceil($current / $pagesize); #1-based
		
		$startofpage = ($pageindex - 1) * $pagesize + 1;
		$endofpage = $startofpage + $pagesize;
		
		$first = "";
		if($pageindex > 2) {
			$first = $this->paginationItem($q, $view, $sort, $pagesize, 1, $total);
		}

		$prev = "";
		if($pageindex > 1) {
			$prev = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex - 1, $total);
		}
		
		$current = $this->paginationItem(null, $view, $sort, $pagesize, $pageindex, $total);

		$next = "";
		if($pageindex < $pagecount) {
			$next = $this->paginationItem($q, $view, $sort, $pagesize, $pageindex + 1, $total);
		}
		
		$last = "";
		if($pageindex < $pagecount - 1) {
			$last = $this->paginationItem($q, $view, $sort, $pagesize, $pagecount, $total);
		}
		
		return $this->renderView('DtdbCardsBundle:Search:pagination.html.twig', array(
			"first" => $first,
			"prev" => $prev,
			"current" => $current,
			"next" => $next,
			"last" => $last,
			"count" => $total,
			"ellipsisbefore" => $pageindex > 3,
			"ellipsisafter" => $pageindex < $pagecount - 2,
		));
	}

}
