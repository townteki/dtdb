<?php

namespace Dtdb\CardsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
	public function setsAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
		
		$data = $this->get('cards_data')->allsetsnocycledata();
		
		$content = json_encode($data);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
		$response->setContent($content);
		return $response;
	}
	
	public function cardAction($card_code)
	{

		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
		
		$conditions = $this->get('cards_data')->syntax($card_code);
		$this->get('cards_data')->validateConditions($conditions);
		$query = $this->get('cards_data')->buildQueryFromConditions($conditions);
		
		$cards = array();
		$last_modified = null;
		if($query && $rows = $this->get('cards_data')->get_search_rows($conditions, "set"))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
			}
			$response->setLastModified($last_modified);
			if ($response->isNotModified($this->getRequest())) {
				return $response;
			}
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				$card = $this->get('cards_data')->getCardInfo($rows[$rowindex], true, "en");
				$cards[] = $card;
			}
		}
		
		$content = json_encode($cards);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
		}
		
		$response->headers->set('Content-Type', 'application/javascript');
		$response->setContent($content);
		return $response;
		
	}

	public function cardsAction()
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
	
		$jsonp = $this->getRequest()->query->get('jsonp');
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$cards = array();
		$last_modified = null;
		if($rows = $this->get('cards_data')->get_search_rows(array(), "set", true))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
			}
			$response->setLastModified($last_modified);
			if ($response->isNotModified($this->getRequest())) {
				return $response;
			}
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				$card = $this->get('cards_data')->getCardInfo($rows[$rowindex], true);
				$cards[] = $card;
			}
		}
	
		$content = json_encode($cards);
		if(isset($jsonp))
		{
			$content = "$jsonp($content)";
			$response->headers->set('Content-Type', 'application/javascript');
		} else
		{
			$response->headers->set('Content-Type', 'application/json');
		}
	
		$response->setContent($content);
		return $response;
	}
	
	public function setAction($pack_code)
	{
		$response = new Response();
		$response->setPublic();
		$response->setMaxAge($this->container->getParameter('long_cache'));
		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
		
		$locale = $this->getRequest()->query->get('_locale');
		if(isset($locale)) $this->getRequest()->setLocale($locale);
	
		$format = $this->getRequest()->getRequestFormat();
		
		$pack = $this->getDoctrine()->getRepository('DtdbCardsBundle:Pack')->findOneBy(array('code' => $pack_code));
		if(!$pack) die();
		
		$conditions = $this->get('cards_data')->syntax("e:$pack_code");
		$this->get('cards_data')->validateConditions($conditions);
		$query = $this->get('cards_data')->buildQueryFromConditions($conditions);
	
		$cards = array();
		$last_modified = new \DateTime();
		if($query && $rows = $this->get('cards_data')->get_search_rows($conditions, "set"))
		{
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				if(empty($last_modified) || $last_modified < $rows[$rowindex]->getTs()) $last_modified = $rows[$rowindex]->getTs();
			}
			$response->setLastModified($last_modified);
			if ($response->isNotModified($this->getRequest())) {
				return $response;
			}
			for($rowindex = 0; $rowindex < count($rows); $rowindex++) {
				$card = $this->get('cards_data')->getCardInfo($rows[$rowindex], true, "en");
				$cards[] = $card;
			}
		}

		if($format == "json")
		{
			
			$content = json_encode($cards);
			if(isset($jsonp))
			{
				$content = "$jsonp($content)";
				$response->headers->set('Content-Type', 'application/javascript');
			} else
			{
				$response->headers->set('Content-Type', 'application/json');
			}
			$response->setContent($content);
			
		}
		else if($format == "xml")
		{
/*
			$cardsxml = array();
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
				
				if(!isset($card['gangcost'])) {
					$card['gangcost'] = '';
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
			$response->setContent($this->renderView('DtdbCardsBundle::apiset.xml.twig', array(
				"name" => $pack->getName(),
				"cards" => $cardsxml,
			)));
*/
		}
        else if($format == 'xlsx')
        {
            $columns = array(
                    "code" => "Code",
                    "pack" => "Pack",
                    "number" => "Number",
                    "title" => "Name",
                    "cost" => "Cost",
                    "type" => "Type",
                    "suit" => "Suit",
                    "value" => "Value",
                    "keywords" => "Keywords",
                    "text" => "Text",
                    "gang" => "Gang",
                    "gang_letter" => "Gang",
                    "illustrator" => "Illustrator",
                    "flavor" => "Flavor text",
                    "quantity" => "Qty",
                    "shooter" => "Shooter"
            );
            $phpExcelObject = $this->get('phpexcel')->createPHPExcelObject();
            $phpExcelObject->getProperties()->setCreator("dtdb")
                       ->setLastModifiedBy($last_modified->format('Y-m-d'))
                       ->setTitle($pack->getName())
                       ->setSubject($pack->getName())
                       ->setDescription($pack->getName() . " Cards Description")
                       ->setKeywords("doomtown reloaded ".$pack->getName());
            $phpActiveSheet = $phpExcelObject->setActiveSheetIndex(0);
            
            $col_index = 0;
            foreach($columns as $key => $label)
            {
                $phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, 1);
                $phpCell->setValue($label);
            }
            
            foreach($cards as $row_index => $card)
            {
                $col_index = 0;
                foreach($columns as $key => $label)
                {
                    $value = isset($card[$key]) ? $card[$key] : '';
                    $phpCell = $phpActiveSheet->getCellByColumnAndRow($col_index++, $row_index+2);
                    if($key == 'code')
                    {
                        $phpCell->setValueExplicit($value, 's');
                    }
                    else
                    {
                        $phpCell->setValue($value);
                    }
                }
            }
                
            $writer = $this->get('phpexcel')->createWriter($phpExcelObject, 'Excel2007');
            $response = $this->get('phpexcel')->createStreamedResponse($writer);
            $response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
            $response->headers->set('Content-Disposition', 'attachment;filename='.$pack->getName().'.xlsx');
    		$response->headers->add(array('Access-Control-Allow-Origin' => '*'));
        }
		
		return $response;
	}
	
}