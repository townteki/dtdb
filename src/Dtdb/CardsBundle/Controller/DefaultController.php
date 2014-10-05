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

	function apidocAction()
	{
		
		$response = new Response();
		$response->setPrivate();
		
		return $this->render('DtdbCardsBundle:Default:apidoc.html.twig', array(
		        "pagetitle" => "API documentation",
		), $response);
	}

	public function ffgAction()
	{
	    // http://www.fantasyflightgames.com/ffg_content/organized-play/2013/private-security-force.png
	    $em = $this->get('doctrine')->getManager();
	    
	    $fs = new \Symfony\Component\Filesystem\Filesystem();
	    
	    $old = array();
	    $new = array();
	    $fails = array();
	    
	    $base = 'http://www.fantasyflightgames.com/ffg_content/android-netrunner';
	    $root = $this->get('kernel')->getRootDir()."/../web/ffg_images";
	    
	    $segments = array(
	    	'core' => 'core-set-cards',
	        'genesis' => 'genesis-cycle/cards',
	        'creation-and-control' => 'deluxe-expansions/creation-and-control',
	        'spin' => 'spin-cycle/cards',
	        'honor-and-profit' => 'deluxe-expansions/honor-and-profit/cards',
	        'lunar' => 'lunar-cycle/cards',
	    );
	    
	    $corrections = array(
	    	'astroscript-pilot-program' => 'autoscript-pilot-program',
	            'melange-mining-corp-' => 'melange-mining-corp',
	            'haas-bioroid-stronger-together' => 'haas-bioroid-adn02',
	            'ash-2x3zb9cy' => 'ash',
	            'dj-vu' => 'deja-vu',
	            'drac' => 'draco',
	            'joshua-b-' => 'joshua-b',
	            'doppelgnger' => 'doppelganger',
	            'weyland-consortium-because-we-built-it' => 'weyland-consortium',
	            'mr--li' => 'mr-li',
	    );
	    
	    $cycles = $em->getRepository('DtdbCardsBundle:Cycle')->findBy(array(), array('number' => 'asc'));
	    /* @var $cycle Cycle */
	    foreach ($cycles as $cycle) {
	    	if(!isset($segments[$cycle->getCode()])) {
	            continue;
	        }
	        $segment = $segments[$cycle->getCode()];
	        
	        $packs = $cycle->getPacks();
	        /* @var $pack Pack */
	        foreach($packs as $pack) {
	            $cards = $pack->getCards();
	            /* @var $card Card */
	            foreach($cards as $card) {
	                $filepath = $root."/".$card->getCode().".png";
	                $imgpath = "/web/ffg_images/".$card->getCode().".png";
	                if(file_exists($filepath)) {
	                    $old[] = $imgpath;
	                    continue;
	                }
	                
	                $ffg = $title = $card->getTitle();
	                $ffg = str_replace(' ', '-', $ffg);
	                $ffg = str_replace('.', '-', $ffg);
	                $ffg = str_replace('&', '-', $ffg);
	                $ffg = str_replace('\'', '', $ffg);
                    $ffg = str_replace(':', '', $ffg);
	                $ffg = strtolower($ffg);
	                $ffg = iconv('UTF-8', 'ASCII//TRANSLIT', $ffg);
	                $ffg = preg_replace('/[^a-z0-9\-]/', '', $ffg);
	                
	                if(isset($corrections[$ffg])) {
	                    $ffg = $corrections[$ffg];
	                }
	                
	                $url = "$base/$segment/$ffg.png";
	                
	                $ch = curl_init($url);
	                curl_setopt($ch,CURLOPT_RETURNTRANSFER, true);
	                if($response = curl_exec($ch)) {
	                    $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
	                    if($content_type === "image/png") {
	                        $fs->dumpFile($filepath, $response);
	                        $new[] = $imgpath;
	                        continue;
	                    }
	                }
	                
	                $fails[] = $url;
	            }
	        }
	    }
	    print "<h2>Fails</h2>";
	    foreach($fails as $fail) { print "<p>$fail</p>"; }
	    print "<h2>New</h2>";
	    foreach($new as $img) { print "<p><img src='$img'></p>"; }
	    print "<h2>Old</h2>";
	    foreach($old as $img) { print "<p><img src='$img'></p>"; }
	    return new Response('');
	}
}
