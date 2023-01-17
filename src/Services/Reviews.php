<?php

namespace App\Services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PDO;

class Reviews
{
    protected EntityManager $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function recent($start = 0, $limit = 30)
    {
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                r.id,
                r.datecreation,
                r.text,
                r.rawtext,
                r.nbvotes,
                c.id card_id,
                c.title card_title,
                c.code card_code,
                p.name pack_name,
                u.id user_id,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation
                from review r
                join user u on r.user_id=u.id
                join card c on r.card_id=c.id
                join pack p on c.pack_id=p.id
                order by r.datecreation desc
                limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "reviews" => $rows
        );
    }
}
