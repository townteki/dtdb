<?php


namespace Dtdb\BuilderBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

/*
 *
 */
class CardsData
{
	public function __construct(Registry $doctrine, RequestStack $request_stack, Router $router, $dir) {
		$this->doctrine = $doctrine;
        $this->request_stack = $request_stack;
        $this->router = $router;
        $this->dir = $dir;
	}

	public function allsetsnocycledata()
	{
		$list_packs = $this->doctrine->getRepository('DtdbBuilderBundle:Pack')->findBy(array(), array("released" => "ASC", "number" => "ASC"));
		$packs = array();
		$sreal=0; $smax = 0;
		foreach($list_packs as $pack) {
			$real = count($pack->getCards());
			$sreal += $real;
			$max = $pack->getSize();
			$smax += $max;
			$packs[] = array(
					"name" => $pack->getName($this->request_stack->getCurrentRequest()->getLocale()),
					"code" => $pack->getCode(),
					"number" => $pack->getNumber(),
					"cyclenumber" => $pack->getCycle()->getNumber(),
					"available" => $pack->getReleased() ? $pack->getReleased()->format('Y-m-d') : '',
					"known" => intval($real),
					"total" => $max,
					"url" => $this->router->generate('cards_list', array('pack_code' => $pack->getCode()), true),
			);
		}
		return $packs;
	}

	public function allsetsdata()
	{
		$list_cycles = $this->doctrine->getRepository('DtdbBuilderBundle:Cycle')->findBy(array(), array("number" => "ASC"));
		$packs = array();
		foreach($list_cycles as $cycle) {
			$sreal=0; $smax = 0;
			foreach($cycle->getPacks() as $pack) {
				$real = count($pack->getCards());
				$sreal += $real;
				$max = $pack->getSize();
				$smax += $max;
				$packs[] = array(
						"name" => $pack->getName($this->request_stack->getCurrentRequest()->getLocale()),
						"code" => $pack->getCode(),
						"available" => $pack->getReleased() ? $pack->getReleased()->format('Y-m-d') : '',
						"known" => intval($real),
						"total" => $max,
						"url" => $this->router->generate('cards_list', array('pack_code' => $pack->getCode()), true),
						"search" => "e:".$pack->getCode(),
						"packs" => '',
				);
			}
		}
		return $packs;
	}


	public function get_search_rows($conditions, $sortorder, $forceempty = false)
	{
		$i=0;
		$gang_codes = array(
			'e' => "Eagle Wardens",
			'f' => "The Fourth Ring",
			'l' => "Law Dogs",
			'm' => "Morgan Cattle Co.",
			'r' => "The 108 Righteous Bandits",
			's' => "The Sloane Gang"
		);

		// construction de la requete sql
		$qb = $this->doctrine->getRepository('DtdbBuilderBundle:Card')->createQueryBuilder('c');
		$qb->leftJoin('c.pack', 'p')
			->leftJoin('p.cycle', 'y')
			->leftJoin('c.type', 't')
			->leftJoin('c.gang', 'g')
			->leftJoin('t.suit', 's')
			->leftJoin('c.shooter', 'h');

		foreach($conditions as $condition)
		{
			$type = array_shift($condition);
			$operator = array_shift($condition);
			switch($type)
			{
				case '': // title or index
					$or = array();
					foreach($condition as $arg) {
						$code = preg_match('/^\d\d\d\d\d$/u', $arg);
						$acronym = preg_match('/^[A-Z]{2,}$/', $arg);
						if($code) {
							$or[] = "(c.code = ?$i)";
							$qb->setParameter($i++, $arg);
						} else if($acronym) {
							$or[] = "(BINARY(c.title) like ?$i)";
							$qb->setParameter($i++, "%$arg%");
							$like = implode('% ', str_split($arg));
							$or[] = "(REPLACE(c.title, '-', ' ') like ?$i)";
							$qb->setParameter($i++, "$like%");
						} else {
							$or[] = "(c.title like ?$i)";
							$qb->setParameter($i++, "%$arg%");
						}
					}
					$qb->andWhere(implode(" or ", $or));
					break;
				case 'x': // text
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.text like ?$i)"; break;
							case '!': $or[] = "(c.text not like ?$i)"; break;
						}
						$qb->setParameter($i++, "%$arg%");
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'f': // flavor
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.flavor like ?$i)"; break;
							case '!': $or[] = "(c.flavor not like ?$i)"; break;
						}
						$qb->setParameter($i++, "%$arg%");
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'e': // expansion
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(p.code = ?$i)"; break;
							case '!': $or[] = "(p.code != ?$i)"; break;
							case '<':
							    if(!isset($qb2)) {
							        $qb2 = $this->doctrine->getRepository('DtdbBuilderBundle:Pack')->createQueryBuilder('p2');
							        $or[] = $qb->expr()->lt('p.released', '(' . $qb2->select('p2.released')->where("p2.code = ?$i")->getDql() . ')');
							    }
							    break;
							case '>':
							    if(!isset($qb3)) {
							        $qb3 = $this->doctrine->getRepository('DtdbBuilderBundle:Pack')->createQueryBuilder('p3');
							        $or[] = $qb->expr()->gt('p.released', '(' . $qb3->select('p3.released')->where("p3.code = ?$i")->getDql() . ')');
							    }
							    break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 't': // type
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(t.name = ?$i)"; break;
							case '!': $or[] = "(t.name != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 's': // shooter
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(h.name = ?$i)"; break;
							case '!': $or[] = "(h.name != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'g': // gang
					$or = array();
					foreach($condition as $arg) {
						if(array_key_exists($arg, $gang_codes)) {
							switch($operator) {
								case ':': $or[] = "(g.name = ?$i)"; break;
								case '!': $or[] = "(g.name != ?$i)"; break;
							}
							$qb->setParameter($i++, $gang_codes[$arg]);
						} else if($arg === '-') {
						    switch($operator) {
						    	case ':': $or[] = "(g.name is null)"; break;
						    	case '!': $or[] = "(g.name is not null)"; break;
						    }
						}
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'k': // keywords
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':':
								$or[] = "((c.keywords = ?$i) or (c.keywords like ?".($i+1).") or (c.keywords like ?".($i+2).") or (c.keywords like ?".($i+3)."))";
								$qb->setParameter($i++, "$arg");
								$qb->setParameter($i++, "$arg %");
								$qb->setParameter($i++, "% $arg");
								$qb->setParameter($i++, "% $arg %");
								break;
							case '!':
								$or[] = "(c.keywords is null or ((c.keywords != ?$i) and (c.keywords not like ?".($i+1).") and (c.keywords not like ?".($i+2).") and (c.keywords not like ?".($i+3).")))";
								$qb->setParameter($i++, "$arg");
								$qb->setParameter($i++, "$arg %");
								$qb->setParameter($i++, "% $arg");
								$qb->setParameter($i++, "% $arg %");
								break;
						}
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'a': // artist
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.illustrator = ?$i)"; break;
							case '!': $or[] = "(c.illustrator != ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'r': // ghost rock (cost)
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.cost = ?$i)"; break;
							case '!': $or[] = "(c.cost != ?$i)"; break;
							case '<': $or[] = "(c.cost < ?$i)"; break;
							case '>': $or[] = "(c.cost > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'w': // wealth
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.wealth = ?$i)"; break;
							case '!': $or[] = "(c.wealth != ?$i)"; break;
							case '<': $or[] = "(c.wealth < ?$i)"; break;
							case '>': $or[] = "(c.wealth > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'i': // influence
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.influence = ?$i)"; break;
							case '!': $or[] = "(c.influence != ?$i)"; break;
							case '<': $or[] = "(c.influence < ?$i)"; break;
							case '>': $or[] = "(c.influence > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'b': // bullets
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.bullets = ?$i)"; break;
							case '!': $or[] = "(c.bullets != ?$i)"; break;
							case '<': $or[] = "(c.bullets < ?$i)"; break;
							case '>': $or[] = "(c.bullets > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'u': // upkeep
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.upkeep = ?$i)"; break;
							case '!': $or[] = "(c.upkeep != ?$i)"; break;
							case '<': $or[] = "(c.upkeep < ?$i)"; break;
							case '>': $or[] = "(c.upkeep > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'p': // production
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.production = ?$i)"; break;
							case '!': $or[] = "(c.production != ?$i)"; break;
							case '<': $or[] = "(c.production < ?$i)"; break;
							case '>': $or[] = "(c.production > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'c': // control
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case ':': $or[] = "(c.control = ?$i)"; break;
							case '!': $or[] = "(c.control != ?$i)"; break;
							case '<': $or[] = "(c.control < ?$i)"; break;
							case '>': $or[] = "(c.control > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'v': // rank
					$or = array();
					$ranks = array('A' => 1, 'J' => 11, 'Q' => 12, 'K' => 13, '*' => 'null');
					foreach($condition as $arg) {
					    if(isset($ranks[$arg])) $arg = $ranks[$arg];
					    switch($operator) {
							case ':': $or[] = "(c.rank = ?$i)"; break;
							case '!': $or[] = "(c.rank != ?$i)"; break;
							case '<': $or[] = "(c.rank < ?$i)"; break;
							case '>': $or[] = "(c.rank > ?$i)"; break;
						}
						$qb->setParameter($i++, $arg);
					}
					$qb->andWhere(implode($operator == '!' ? " and " : " or ", $or));
					break;
				case 'd': // date
					$or = array();
					foreach($condition as $arg) {
						switch($operator) {
							case '<': $or[] = "(p.released <= ?$i)"; break;
							case '>': $or[] = "(p.released > ?$i or p.released IS NULL)"; break;
						}
						if($arg == "now") $qb->setParameter($i++, new \DateTime());
						else $qb->setParameter($i++, new \DateTime($arg));
					}
					$qb->andWhere(implode(" or ", $or));
					break;
			}
		}

		if(!$i && !$forceempty) {
			return;
		}
		switch($sortorder) {
			case 'set': $qb->orderBy('c.code'); break;
			case 'gang': $qb->addOrderBy('c.gang')->addOrderBy('c.type'); break;
			case 'type': $qb->addOrderBy('c.type')->addOrderBy('c.gang'); break;
			case 'cost': $qb->orderBy('c.type')->addOrderBy('c.cost'); break;
			case 'rank': $qb->orderBy('c.rank')->addOrderBy('c.type')->addOrderBy('c.gang'); break;
		}
		$qb->addOrderBy('c.title');
		$qb->addOrderBy('c.code');
		$query = $qb->getQuery();
		$rows = $query->getResult();

		for($i=0; $i<count($rows); $i++)
		{
			while(isset($rows[$i+1]) && $rows[$i]->getTitle() == $rows[$i+1]->getTitle() && $rows[$i]->getPack()->getCode() == "alt")
			{
				$rows[$i] = $rows[$i+1];
				array_splice($rows, $i+1, 1);
			}
		}

		return $rows;
	}

	public function getCardAlternatives($card)
	{
		$qb = $this->doctrine->getRepository('DtdbBuilderBundle:Card')->createQueryBuilder('c');
		$qb->andWhere("c.title = ?1")->setParameter(1, $card->getTitle());
		$qb->andWhere("c.code != ?2")->setParameter(2, $card->getCode());
		$qb->orderBy('c.code');
		$query = $qb->getQuery();
		$rows = $query->getResult();
		$alternatives = array();
		foreach($rows as $alt)
		{
			if($alt->getPack()->getId() == $card->getPack()->getId()) continue;
			$alternatives[] = array(
				"pack" => $alt->getPack()->getName($this->request_stack->getCurrentRequest()->getLocale()),
				"set_code" => $alt->getPack()->getCode(),
				"number" => $alt->getNumber(),
				"code" => $alt->getCode(),
				"url" => $this->router->generate('cards_zoom', array('card_code' => $alt->getCode()), true),
			);
		}
		return $alternatives;
	}

	/**
	 *
	 * @param \Dtdb\BuilderBundle\Entity\Card $card
	 * @param string $api
	 * @return multitype:multitype: string number mixed NULL unknown
	 */
	public function getCardInfo($card, $api = false)
	{
	    static $cache = array();
	    static $cacheApi = array();

	    $locale = $this->request_stack->getCurrentRequest()->getLocale();

	    if(!$api && isset($cache[$card->getId()]) && isset($cache[$card->getId()][$locale])) {
	        return $cache[$card->getId()][$locale];
	    }
	    if($api && isset($cacheApi[$card->getId()]) && isset($cacheApi[$card->getId()][$locale])) {
	        return $cacheApi[$card->getId()][$locale];
	    }

		$dbh = $this->doctrine->getConnection();

		$cardinfo = array(
				"id" => $card->getId(),
				"last-modified" => $card->getTs()->format('c'),
				"code" => $card->getCode(),
				"title" => $card->getTitle(),
				"type" => $card->getType()->getName(),
				"type_code" => strtolower($card->getType()->getName()),
		        "suit" => $card->getType()->getSuit() ? $card->getType()->getSuit()->getName() : NULL,
				"keywords" => $card->getKeywords() ? $card->getKeywords() : '',
				"text" => $card->getText() ? $card->getText() : '',
				"cost" => $card->getCost(),
				"gang" => $card->getGang() ? $card->getGang()->getName() : 'Neutral',
				"gang_code" => $card->getGang() ? $card->getGang()->getCode() : 'neutral',
				"gang_letter" => $card->getGang() ? substr($card->getGang()->getCode(), 0, 1) : '-',
				"flavor" => $card->getFlavor($locale) ? $card->getFlavor($locale) : '',
				"illustrator" => $card->getIllustrator() ? $card->getIllustrator() : '',
				"number" => $card->getNumber(),
				"quantity" => $card->getQuantity(),
				"id_set" => $card->getPack()->getId(),
				"pack" => $card->getPack()->getName(),
				"pack_code" => $card->getPack()->getCode(),
		        "cyclenumber" => $card->getPack()->getCycle()->getNumber(),
		        "shooter" => $card->getShooter() ? $card->getShooter()->getName() : '',
		        "rank" => $card->getRank(),
		        "upkeep" => $card->getUpkeep(),
		        "production" => $card->getProduction(),
		        "bullets" => $card->getBullets(),
		        "influence" => $card->getInfluence(),
		        "control" => $card->getControl(),
		        "wealth" => $card->getWealth(),
			 "octgnid" => $card->getOctgnid()
		);

		$cardinfo['value'] = $cardinfo['suit'].$cardinfo['rank'];
		$cardinfo['url'] = $this->router->generate('cards_zoom', array('card_code' => $card->getCode(), '_locale' => $locale), true);

		$cardinfo['imagesrc'] = "";

		if($locale != 'en' && file_exists($this->dir . DIRECTORY_SEPARATOR . $locale . DIRECTORY_SEPARATOR . $card->getCode() . ".jpg"))
		{
			$cardinfo['imagesrc'] = "/web/bundles/dtdbbuilder/images/cards/$locale/". $card->getCode() . ".jpg";
		}
		else if(file_exists($this->dir . DIRECTORY_SEPARATOR . "en" . DIRECTORY_SEPARATOR . $card->getCode() . ".jpg"))
		{
		    $cardinfo['imagesrc'] = "/web/bundles/dtdbbuilder/images/cards/en/". $card->getCode() . ".jpg";
		}

		if($api) {
			unset($cardinfo['id']);
			unset($cardinfo['id_set']);
			//$cardinfo = array_filter($cardinfo, function ($var) { return isset($var); });
			$cacheApi[$card->getId()][$locale] = $cardinfo;
		} else {
			$cardinfo['text'] = implode(array_map(function ($l) { return "<p>$l</p>"; }, explode("\r\n", $cardinfo['text'])));
		    $cache[$card->getId()][$locale] = $cardinfo;
		}

		return $cardinfo;
	}

	public function syntax($query)
	{
		// renvoie une liste de conditions (array)
		// chaque condition est un tableau à n>1 éléments
		// le premier est le type de condition (0 ou 1 caractère)
		// les suivants sont les arguments, en OR

		$query = preg_replace('/\s+/u', ' ', trim($query));

		$list = array();
		$cond;
		// l'automate a 3 états :
		// 1:recherche de type
		// 2:recherche d'argument principal
		// 3:recherche d'argument supplémentaire
		// 4:erreur de parsing, on recherche la prochaine condition
		// s'il tombe sur un argument alors qu'il est en recherche de type, alors le type est vide
		$etat = 1;
		while($query != "") {
			if($etat == 1) {
				if(isset($cond) && $etat != 4 && count($cond)>2) {
					$list[] = $cond;
				}
				// on commence par rechercher un type de condition
				if(preg_match('/^(\p{L})([:<>!])(.*)/u', $query, $match)) { // jeton "condition:"
					$cond = array(mb_strtolower($match[1]), $match[2]);
					$query = $match[3];
				} else {
					$cond = array("", ":");
				}
				$etat=2;
			} else {
				if( preg_match('/^"([^"]*)"(.*)/u', $query, $match) // jeton "texte libre entre guillements"
				 || preg_match('/^([\p{L}\p{N}\-]+)(.*)/u', $query, $match) // jeton "texte autorisé sans guillements"
				) {
					if(($etat == 2 && count($cond)==2) || $etat == 3) {
						$cond[] = $match[1];
						$query = $match[2];
						$etat = 2;
					} else {
						// erreur
						$query = $match[2];
						$etat = 4;
					}
				} else if( preg_match('/^\|(.*)/u', $query, $match) ) { // jeton "|"
					if(($cond[1] == ':' || $cond[1] == '!') && (($etat == 2 && count($cond)>2) || $etat == 3)) {
						$query = $match[1];
						$etat = 3;
					} else {
						// erreur
						$query = $match[1];
						$etat = 4;
					}
				} else if( preg_match('/^ (.*)/u', $query, $match) ) { // jeton " "
					$query = $match[1];
					$etat=1;
				} else {
					// erreur
					$query = substr($query, 1);
					$etat = 4;
				}
			}
		}
		if(isset($cond) && $etat != 4 && count($cond)>2) {
			$list[] = $cond;
		}
		return $list;
	}

	public function validateConditions(&$conditions)
	{
		// suppression des conditions invalides
		$canDoNumeric = array('r', 'v', 'u', 'p', 'b', 'i', 'c', 'e', 'q');
		$numeric = array('<', '>');
		$gangs = array('e','f','l','m','r','s','-');
		foreach($conditions as $i => $l)
		{
			if(in_array($l[1], $numeric) && !in_array($l[0], $canDoNumeric)) unset($conditions[$i]);
			if($l[0] == 'g')
			{
				$conditions[$i][2] = substr($l[2],0,1);
				if(!in_array($conditions[$i][2], $gangs)) unset($conditions[$i]);
			}
		}
	}

	public function buildQueryFromConditions($conditions)
	{
		// reconstruction de la bonne chaine de recherche pour affichage
		return implode(" ", array_map(
				function ($l) {
					return ($l[0] ? $l[0].$l[1] : "")
					. implode("|", array_map(
							function ($s) {
								return preg_match("/^[\p{L}\p{N}\-]+$/u", $s) ?$s : "\"$s\"";
							},
							array_slice($l, 2)
					));
				},
				$conditions
		));
	}


	public function get_reviews($card)
	{
	    $reviews = $this->doctrine->getRepository('DtdbBuilderBundle:Review')->findBy(array('card' => $card), array('nbvotes' => 'DESC'));

	    $response = array();
	    foreach($reviews as $review) {
	        /* @var $review \Dtdb\BuilderBundle\Entity\Review */
	        $user = $review->getUser();
	        $datecreation = $review->getDatecreation();
	        $response[] = array(
	                'id' => $review->getId(),
	                'text' => $review->getText(),
	                'author_id' => $user->getId(),
	                'author_name' => $user->getUsername(),
	                'author_reputation' => $user->getReputation(),
	                'author_color' => $user->getGang(),
	                'datecreation' => $datecreation,
	                'nbvotes' => $review->getNbvotes()
	        );
	    }

	    return $response;
	}


}
