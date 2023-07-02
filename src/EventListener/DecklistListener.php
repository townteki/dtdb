<?php

namespace App\EventListener;

use App\Entity\Decklist;
use App\Entity\Pack;
use App\Services\Decks;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;

class DecklistListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Decklist) {
            $this->setFormat($entity);
        }
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        if ($entity instanceof Decklist) {
            $this->setFormat($entity);
        }
    }

    protected function setFormat(Decklist $decklist)
    {
        $packs = [];
        foreach ($decklist->getSlots() as $slot) {
            $packs[] = $slot->getCard()->getPack();
        }
        usort($packs, function (Pack $a, Pack $b) {
            $rhett = $a->getCycle()->getNumber() <=> $b->getCycle()->getNumber();
            if ($rhett) {
                return $rhett;
            }
            return $a->getNumber() <=> $b->getNumber();
        });
        $earliestPack = $packs[0];
        $isWwe = (
            $earliestPack->getCycle()->getNumber() > Decks::TCAR_CYCLENUMBER
            || ($earliestPack->getCycle()->getNumber() === Decks::TCAR_CYCLENUMBER
                && $earliestPack->getNumber() >= Decks::TCAR_NUMBER
            )
        );
        $decklist->setIsWwe($isWwe);
    }
}
