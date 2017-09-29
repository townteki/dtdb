<?php

namespace Dtdb\BuilderBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Dtdb\BuilderBundle\Entity\Card;
use Dtdb\BuilderBundle\Entity\Pack;
use Dtdb\BuilderBundle\Entity\Cycle;

class DefaultController extends Controller
{
	public function searchAction()
	{
	    $response = new Response();
	    $response->setPublic();
	    $response->setMaxAge($this->container->getParameter('long_cache'));

		$dbh = $this->get('doctrine')->getConnection();

		$list_packs = $this->getDoctrine()->getRepository('DtdbBuilderBundle:Pack')->findBy(array(), array("released" => "ASC", "number" => "ASC"));
		$packs = array();
		foreach($list_packs as $pack) {
			$packs[] = array(
					"name" => $pack->getName($this->getRequest()->getLocale()),
					"code" => $pack->getCode(),
			);
		}

		$list_cycles = $this->getDoctrine()->getRepository('DtdbBuilderBundle:Cycle')->findBy(array(), array("number" => "ASC"));
		$cycles = array();
		foreach($list_cycles as $cycle) {
			$cycles[] = array(
					"name" => $cycle->getName($this->getRequest()->getLocale()),
					"code" => $cycle->getCode(),
			);
		}

		$list_types = $this->getDoctrine()->getRepository('DtdbBuilderBundle:Type')->findBy(array(), array("name" => "ASC"));
		$types = array_map(function ($type) {
			return strtolower($type->getName());
		}, $list_types);

		$list_gangs = $this->getDoctrine()->getRepository('DtdbBuilderBundle:gang')->findBy(array(), array("id" => "ASC"));
		$gangs = array();
		foreach($list_gangs as $gang) {
			$gangs[] = array(
					"name" => $gang->getName($this->getRequest()->getLocale()),
					"code" => $gang->getCode(),
					"code1" => $gang->getCode()[0],
			);
		}
		$gangs[] = array(
				"name" => "Neutral",
				"code" => "neutral",
				"code1" => '-',
		);

		$list_shooters = $this->getDoctrine()->getRepository('DtdbBuilderBundle:shooter')->findBy(array(), array("name" => "ASC"));
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

		return $this->render('DtdbBuilderBundle:Search:searchform.html.twig', array(
		        "pagetitle" => "Card Search",
				"packs" => $packs,
				"cycles" => $cycles,
				"types" => $types,
				"gangs" => $gangs,
		        "shooters" => $shooters,
				"keywords" => $keywords,
				"illustrators" => $illustrators,
				"allsets" => $this->renderView('DtdbBuilderBundle:Default:allsets.html.twig', array(
                    "data" => $this->get('cards_data')->allsetsdata(),
		        )),
				'locales' => $this->renderView('DtdbBuilderBundle:Default:langs.html.twig'),
		), $response);
	}

	function aboutAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:about.html.twig', array(
		        "pagetitle" => "About",
		), $response);
	}

	function changelogAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:changelog.html.twig', array(
		        "pagetitle" => "Change Log",
		), $response);
	}

	function rulesAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:rules.html.twig', array(
		        "pagetitle" => "Rules",
		), $response);
	}

	function extraRulesAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:extraRules.html.twig', array(
		        "pagetitle" => "Additional Rules",
		), $response);
	}

	function structureAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:structure.html.twig', array(
		        "pagetitle" => "Turn Structure",
		), $response);
	}

	function faqAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:faq.html.twig', array(
		        "pagetitle" => "FAQ",
		), $response);
	}

	function floorRulesAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:floorRules.html.twig', array(
		        "pagetitle" => "Tournament Rules",
		), $response);
	}

	function octgnGuideAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:octgnGuide.html.twig', array(
		        "pagetitle" => "Guide to OCTGN",
		), $response);
	}

	function collectedRulingsAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:collectedRulings.html.twig', array(
		        "pagetitle" => "Collected Rulings",
		), $response);
	}

	function oldRulesAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:oldRules.html.twig', array(
		        "pagetitle" => "Rules Archives",
		), $response);
	}

	function oldFaqsAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:oldFaqs.html.twig', array(
		        "pagetitle" => "FAQs Archives",
		), $response);
	}

	function apidocAction()
	{

		$response = new Response();
		$response->setPrivate();

		return $this->render('DtdbBuilderBundle:Default:apidoc.html.twig', array(
		        "pagetitle" => "API documentation",
		), $response);
	}

	public function profileAction()
	{
		$user = $this->getUser();

		$gangs = $this->get('doctrine')->getRepository('DtdbBuilderBundle:Gang')->findAll();
		foreach($gangs as $i => $gang) {
			$gangs[$i]->localizedName = $gang->getName($this->getRequest()->getLocale());
		}

		return $this->render('DtdbBuilderBundle:Default:profile.html.twig', array(
				'user'=> $user, 'gangs' => $gangs));
	}

	public function saveProfileAction()
	{
		/* @var $user \Dtdb\BuilderBundle\Entity\User */
		$user = $this->getUser();
		$request = $this->getRequest();

		$resume = filter_var($request->get('resume'), FILTER_SANITIZE_STRING);
		$gang_code = filter_var($request->get('user_gang_code'), FILTER_SANITIZE_STRING);
		$notifAuthor = $request->get('notif_author') ? TRUE : FALSE;
		$notifCommenter = $request->get('notif_commenter') ? TRUE : FALSE;
		$notifMention = $request->get('notif_mention') ? TRUE : FALSE;

		$user->setGang($gang_code);
		$user->setResume($resume);
		$user->setNotifAuthor($notifAuthor);
		$user->setNotifCommenter($notifCommenter);
		$user->setNotifMention($notifMention);

		$this->get('doctrine')->getManager()->flush();

			$this->get('session')
					->getFlashBag()
					->set('notice', "Successfully saved your profile.");

		return $this->redirect($this->generateUrl('user_profile'));
	}

	public function infoAction(Request $request)
	{
			$jsonp = $request->query->get('jsonp');
			$locale = $request->query->get('_locale');
			if(isset($locale)) $request->setLocale($locale);
			$locale = $request->getLocale();

			$decklist_id = $request->query->get('decklist_id');
			$card_id = $request->query->get('card_id');

			$content = null;

			/* @var $user \Dtdb\BuilderBundle\Entity\User */
			$user = $this->getUser();
			if($user)
			{
					$user_id = $user->getId();

					$public_profile_url = $this->get('router')->generate('user_profile_view', array(
									'_locale' => $this->getRequest()->getLocale(),
									'user_id' => $user_id,
									'user_name' => urlencode($user->getUsername())
					));
					$content = array(
									'public_profile_url' => $public_profile_url,
									'id' => $user_id,
									'name' => $user->getUsername(),
									'gang' => $user->getGang(),
									'locale' => $locale
					);

					if(isset($decklist_id)) {
							/* @var $em \Doctrine\ORM\EntityManager */
							$em = $this->get('doctrine')->getManager();
							/* @var $decklist \Dtdb\BuilderBundle\Entity\Decklist */
							$decklist = $em->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);

			if ($decklist) {
					$decklist_id = $decklist->getId();

					$dbh = $this->get('doctrine')->getConnection();

						$content['is_liked'] = (boolean) $dbh->executeQuery("SELECT
							count(*)
							from decklist d
							join vote v on v.decklist_id=d.id
							where v.user_id=?
							and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

						$content['is_favorite'] = (boolean) $dbh->executeQuery("SELECT
							count(*)
							from decklist d
							join favorite f on f.decklist_id=d.id
							where f.user_id=?
							and d.id=?", array($user_id, $decklist_id))->fetch(\PDO::FETCH_NUM)[0];

						$content['is_author'] = ($user_id == $decklist->getUser()->getId());

						$content['can_delete'] = ($decklist->getNbcomments() == 0) && ($decklist->getNbfavorites() == 0) && ($decklist->getNbvotes() == 0);
			}
					}

					if(isset($card_id)) {
							/* @var $em \Doctrine\ORM\EntityManager */
							$em = $this->get('doctrine')->getManager();
							/* @var $card \Dtdb\BuilderBundle\Entity\Card */
							$card = $em->getRepository('DtdbBuilderBundle:Card')->find($card_id);

							if($card) {
									$reviews = $card->getReviews();
									/* @var $review \Dtdb\BuilderBundle\Entity\Review */
									foreach($reviews as $review) {
											if($review->getUser()->getId() === $user->getId()) {
													$content['review_id'] = $review->getId();
													$content['review_text'] = $review->getRawtext();
											}
									}
							}
					}
			}
			$content = json_encode($content);

			$response = new Response();
			$response->setPrivate();
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
}
