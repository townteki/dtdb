<?php

namespace App\Repository;

use App\Entity\Card;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

class CardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Card::class);
    }
    /**
     * Check the entire card-pool for duplicates by title and flag them accordingly.
     * @return void
     */
    public function updateIsMultipleFlagOnAllCards(): void
    {
        // for performance reasons, run this through DBAL instead of using entities.

        // 1. find all card titles that are not unique
        // SELECT title, COUNT(title) AS c FROM card GROUP BY title HAVING c > 1 ORDER BY title;
        $qb = $this->createQueryBuilder('card')
            ->select('card.title, COUNT(card.title) AS c')
            ->groupBy('card.title')
            ->having('c > 1')
            ->orderBy('card.title');
        $rs = $qb->getQuery()->getResult();
        $titles = array_unique(array_column($rs, 'title'));

        if (empty($titles)) {
            return;
        }

        // 3. flag all cards as duplicated by title that have those titles
        $qb = $this->createQueryBuilder('card')
            ->update()
            ->set('card.isMultiple', 'true')
            ->where($qb->expr()->in('card.title', ':titles'))
            ->setParameter('titles', $titles, Connection::PARAM_STR_ARRAY);
        $qb->getQuery()->execute();

        // 3. flag all other cards as not duplicated by title
        $qb = $this->createQueryBuilder('card')
            ->update()
            ->set('card.isMultiple', 'false')
            ->where($qb->expr()->notIn('card.title', ':titles'))
            ->setParameter('titles', $titles, Connection::PARAM_STR_ARRAY);
        $qb->getQuery()->execute();
    }
}
