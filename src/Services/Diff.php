<?php

namespace App\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class Diff
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function diffContents($decks)
    {

        // n flat lists of the cards of each decklist
        $ensembles = array();
        foreach ($decks as $deck) {
            $cards = array();
            foreach ($deck as $code => $qty) {
                for ($i = 0; $i < $qty; $i++) {
                    $cards[] = $code;
                }
            }
            $ensembles[] = $cards;
        }

        // 1 flat list of the cards seen in every decklist
        $conjonction = array();
        for ($i = 0; $i < count($ensembles[0]); $i++) {
            $code = $ensembles[0][$i];
            $indexes = array($i);
            for ($j = 1; $j < count($ensembles); $j++) {
                $index = array_search($code, $ensembles[$j]);
                if ($index !== false) {
                    $indexes[] = $index;
                } else {
                    break;
                }
            }
            if (count($indexes) === count($ensembles)) {
                $conjonction[] = $code;
                for ($j = 0; $j < count($indexes); $j++) {
                    $list = $ensembles[$j];
                    array_splice($list, $indexes[$j], 1);
                    $ensembles[$j] = $list;
                }
                $i--;
            }
        }

        $listings = array();
        for ($i = 0; $i < count($ensembles); $i++) {
            $listings[$i] = array_count_values($ensembles[$i]);
        }
        $intersect = array_count_values($conjonction);

        return array($listings, $intersect);
    }
}
