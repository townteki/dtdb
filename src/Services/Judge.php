<?php

namespace App\Services;

use App\Entity\Card;
use App\Entity\Deckslot;
use Doctrine\ORM\EntityManagerInterface;

class Judge
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Decoupe un deckcontent pour son affichage par type
     *
     * @param array $cards
     * @param Card $outfit
     */
    public function classe($cards, $outfit)
    {
        $analyse = $this->analyse($cards);

        $classeur = array();
        /* @var $slot Deckslot */
        foreach ($cards as $elt) {
            /* @var $card Card */
            $card = $elt['card'];
            $qty = $elt['qty'];
            $start = $elt['start'];
            $type = $card->getType()->getName();
            if ($type == "Outfit") {
                continue;
            }
            $elt['gang'] = str_replace(' ', '-', mb_strtolower(($card->getGang() ? $card->getGang()->getName() : '')));

            if (!isset($classeur[$type])) {
                $classeur[$type] = array("qty" => 0, "slots" => array(), "start" => 0);
            }
            $classeur[$type]["slots"][] = $elt;
            $classeur[$type]["qty"] += $qty;
        }
        if (is_string($analyse)) {
            $classeur['problem'] = $this->problem($analyse);
        } else {
            $classeur = array_merge($classeur, $analyse);
        }
        return $classeur;
    }

    /**
     * Analyse un deckcontent et renvoie un code indiquant le pbl du deck
     *
     * @param array $cards
     * @return array|string
     */
    public function analyse($cards)
    {
        $outfit = null;
        $deck = array();
        $deckSize = 0;
        $startSize = 0;
        $startGR = 0;
        $startDudes = array();
        $gangStarting = array();
        $dudes = array();
        $deckComposition = array('Spades' => array(), 'Diams' => array(), 'Hearts' => array(), 'Clubs' => array());

        $nb_jokers = 0;
        foreach ($cards as $elt) {
            $card = $elt['card'];
            $qty = $elt['qty'];
            $start = $elt['start'];
            $suit_name = $card->getType()->getSuit() ? $card->getType()->getSuit()->getName() : null;
            $rank = $card->getRank();
            if ($card->getType()->getName() == "Outfit") {
                $outfit = $card;
                $startGR += $card->getWealth();
            } elseif ($card->getType()->getName() == "Joker") {
                $nb_jokers += $qty;
            } else {
                $deck[] = $card;
                $deckSize += $qty;
                if ($start != 0) {
                    $startGR -= $card->getCost();
                    if ($card->getGang() != null) {
                        $gangStarting[$card->getGang()->getName()] = $card->getGang();
                    }
                }
                if ($card->getType()->getName() == "Dude") {
                    $legalName = preg_replace("/ \(Exp.\d\)/", "", $card->getTitle());
                    if (isset($dudes[$legalName])) {
                        $dudes[$legalName] += $qty;
                    } else {
                        $dudes[$legalName] = $qty;
                    }
                    if ($start != 0) {
                        if (isset($startDudes[$legalName])) {
                            $startDudes[$legalName] = false;
                        } else {
                            $startDudes[$legalName] = true;
                        }
                    }
                }
            }
            if ($rank) {
                if (isset($deckComposition[$suit_name][$rank])) {
                    $deckComposition[$suit_name][$rank] = $deckComposition[$suit_name][$rank] + $qty;
                } else {
                    $deckComposition[$suit_name][$rank] = $qty;
                }
            }
        }

        if (!isset($outfit)) {
            return 'outfit';
        }

        if ($outfit->getTitle() == '108 Worldly Desires') {
            return 'banned';
        }

        if ($nb_jokers > 2) {
            return 'jokers';
        }

        foreach ($deck as $card) {
            $qty = $cards[$card->getCode()]['qty'];

            if ($qty > 4) {
                return 'copies';
            }
        }
        foreach ($dudes as $legalName => $qty) {
            if ($qty > 4) {
                return 'copies';
            }
        }
        foreach ($gangStarting as $gang) {
            if ($gang != $outfit->getGang()) {
                return 'startingposse';
            }
        }

        $nb = 0;
        foreach ($deckComposition as $suit => $suitComposition) {
            foreach ($suitComposition as $rank => $qty) {
                if ($qty > 4) {
                    return 'values';
                }
                $nb += $qty;
            }
        }
        if ($nb != 52) {
            return 'deckSize';
        }
        if ($startGR < 0) {
            return 'startingposse';
        }
        foreach ($startDudes as $legalName => $value) {
            if ($value == false) {
                return 'startingposse';
            }
        }

        return array(
            'deckSize' => $deckSize
        );
    }

    /**
     * @param $problem
     * @return string|void
     */
    public function problem($problem)
    {
        switch ($problem) {
            case 'outfit':
                return "The deck lacks an Outfit card.";
            break;
            case 'deckSize':
                return "The deck doesn't have exactly 52 cards with value.";
            break;
            case 'copies':
                return "The deck has more than 4 copies of a card.";
            break;
            case 'values':
                return "The deck has more than 4 cards with a given value.";
            break;
            case 'jokers':
                return "The deck has more than 2 Jokers.";
            break;
            case 'startingposse':
                return "Illegal starting posse.";
            break;
            case 'banned':
                return "Deck contains cards banned from competitive play.";
            break;
        }
    }
}
