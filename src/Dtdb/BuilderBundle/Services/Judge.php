<?php


namespace Dtdb\BuilderBundle\Services;


/*
 *
 */
class Judge
{
	public function __construct($doctrine) {
		$this->doctrine = $doctrine;
	}
	
	/**
	 * Decoupe un deckcontent pour son affichage par type
	 *
	 * @param \Dtdb\CardsBundle\Entity\Card $outfit
	 */
	public function classe($cards, $outfit)
	{
		$analyse = $this->analyse($cards);
		
		$classeur = array();
		/* @var $slot \Dtdb\BuilderBundle\Entity\Deckslot */
		foreach($cards as $elt) {
			/* @var $card \Dtdb\CardsBundle\Entity\Card */
			$card = $elt['card'];
			$qty = $elt['qty'];
			$type = $card->getType()->getName();
			if($type == "Outfit") continue;
			$elt['gang'] = str_replace(' ', '-', mb_strtolower($card->getGang()->getName()));
			
			if(!isset($classeur[$type])) $classeur[$type] = array("qty" => 0, "slots" => array());
			$classeur[$type]["slots"][] = $elt;
			$classeur[$type]["qty"] += $qty;
		}
		if(is_string($analyse)) {
			$classeur['problem'] = $this->problem($analyse);
		} else {
			$classeur = array_merge($classeur, $analyse);
		}
		return $classeur;
	}
	
    /**
     * Analyse un deckcontent et renvoie un code indiquant le pbl du deck
     *
     * @param array $content
     * @return array
     */
	public function analyse($cards)
	{
		$outfit = null;
		$deck = array();
		$deckSize = 0;
		$deckComposition = array('Spades' => array(), 'Diams' => array(), 'Hearts' => array(), 'Clubs' => array());
		
		$nb_jokers = 0;
		foreach($cards as $elt) {
			$card = $elt['card'];
			$qty = $elt['qty'];
			$suit_name = $card->getType()->getSuit() ? $card->getType()->getSuit()->getName() : null;
			$value = $card->getValue();
			if($card->getType()->getName() == "Outfit") {
				$outfit = $card;
			} else if($card->getType()->getName() == "Joker") {
				$nb_jokers += $qty;
			} else {
				$deck[] = $card;
				$deckSize += $qty;
			}
			if($value) {
			    if(isset($deckComposition[$suit_name][$value])) {
			        $deckComposition[$suit_name][$value] = $deckComposition[$suit_name][$value] + $qty;
			    } else {
			        $deckComposition[$suit_name][$value] = $qty;
			    }
			}
		}
		
		if(!isset($outfit)) {
			return 'outfit';
		}
		
		if($nb_jokers > 2) {
		    return 'jokers';
		}
		
		foreach($deck as $card) {
			$qty = $cards[$card->getCode()]['qty'];
			
			if($qty > 4) {
			    return 'copies';
			}
		}
		
		$nb = 0;
		foreach($deckComposition as $suit => $suitComposition) {
		    foreach($suitComposition as $value => $qty) {
		        if($qty > 4) {
		            return 'values';
		        }
		        $nb += $qty;
		    }
		}
		if($nb != 52) {
		    return 'deckSize';
		}
		
		return array(
			'deckSize' => $deckSize
		);
	}
	
	public function problem($problem)
	{
		switch($problem) {
			case 'outfit': return "The deck lacks an Outfit card."; break;
			case 'deckSize': return "The deck doesn't have exactly 52 cards with value."; break;
			case 'copies' : return "The deck has more than 4 copies of a card."; break;
			case 'values': return "The deck has more than 4 cards with a given value."; break;
			case 'jokers': return "The deck has more than 2 Jokers."; break;
		}
	}
	
}