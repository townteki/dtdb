<?php


namespace Dtdb\BuilderBundle\Services;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

class Reviews
{
    public function __construct(EntityManager $doctrine)
    {
        $this->doctrine = $doctrine;
    }
    
    public function recent($start = 0, $limit = 30)
    {
        /* @var $dbh \Doctrine\DBAL\Driver\PDOConnection */
        $dbh = $this->doctrine->getConnection();
    
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
                limit $start, $limit")->fetchAll(\PDO::FETCH_ASSOC);
    
        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(\PDO::FETCH_NUM)[0];
    
        return array(
                "count" => $count,
                "reviews" => $rows
        );
    
    }
}
