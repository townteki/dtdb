<?php

namespace App\Services;

use Doctrine\DBAL\Driver\PDOConnection;
use Doctrine\ORM\EntityManagerInterface;
use PDO;
use Symfony\Component\HttpFoundation\Request;

class Decklists
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * returns the list of decklist favorited by user
     * @param int $user_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function favorites($user_id, $start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                    d.id,
                    d.name,
                    d.prettyname,
                    d.creation,
                    d.user_id,
                    d.tournament_id,
                    t.description tournament,
                    u.username,
                    u.gang usercolor,
                    u.reputation,
                    u.donation,
                    c.code,
                    d.nbvotes,
                    d.nbfavorites,
                    d.nbcomments
                    from decklist d
                    join user u on d.user_id=u.id
                    join card c on d.outfit_id=c.id
                    join favorite f on f.decklist_id=d.id
                    left join tournament t on d.tournament_id=t.id
                    where f.user_id=?
                    order by creation desc
                    limit $start, $limit",
            array(
                        $user_id
            )
        )
            ->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists published by user
     * @param int $user_id
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function by_author($user_id, $start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                    d.id,
                    d.name,
                    d.prettyname,
                    d.creation,
                    d.user_id,
                    d.tournament_id,
                    t.description tournament,
                    u.username,
                    u.gang usercolor,
                    u.reputation,
                    u.donation,
                    c.code,
                    d.nbvotes,
                    d.nbfavorites,
                    d.nbcomments
                    from decklist d
                    join user u on d.user_id=u.id
                    join card c on d.outfit_id=c.id
                    left join tournament t on d.tournament_id=t.id
                    where d.user_id=?
                    order by creation desc
                    limit $start, $limit",
            array(
                        $user_id
            )
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of recent decklists with large number of votes
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function popular($start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                    d.id,
                    d.name,
                    d.prettyname,
                    d.creation,
                    d.user_id,
                    d.tournament_id,
                    t.description tournament,
                    u.username,
                    u.gang usercolor,
                    u.reputation,
                    u.donation,
                    c.code,
                    d.nbvotes,
                    d.nbfavorites,
                    d.nbcomments,
                    DATEDIFF(CURRENT_DATE, d.creation) nbjours
                    from decklist d
                    join user u on d.user_id=u.id
                    join card c on d.outfit_id=c.id
                    left join tournament t on d.tournament_id=t.id
                    where d.creation > DATE_SUB(CURRENT_DATE, INTERVAL 1 MONTH)
                    order by 2*nbvotes/(1+nbjours*nbjours) DESC, nbvotes desc, nbcomments desc
                    limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists with most number of votes
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function halloffame($start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                left join tournament t on d.tournament_id=t.id
                where nbvotes > 10
                order by nbvotes desc, creation desc
                limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists with large number of recent comments
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function hottopics($start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments,
                (select count(*) from comment where comment.decklist_id=d.id and DATEDIFF(CURRENT_DATE, comment.creation)<1) nbrecentcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                left join tournament t on d.tournament_id=t.id
                where d.nbcomments > 1
                order by nbrecentcomments desc, creation desc
                limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists with a non-null tournament
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function tournaments($start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                c.title identity,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                left join tournament t on d.tournament_id=t.id
                where d.tournament_id is not null
                order by creation desc
                limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists of chosen gang
     * @param string $gang_code
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function gang($gang_code, $start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                join gang f on d.gang_id=f.id
                left join tournament t on d.tournament_id=t.id
                where f.code=?
                order by creation desc
                limit $start, $limit",
            array(
                        $gang_code
            )
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of decklists of chosen datapack
     * @param string $pack_code
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function lastpack($pack_code, $start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                join pack p on d.last_pack_id=p.id
                left join tournament t on d.tournament_id=t.id
                where p.code=?
                order by creation desc
                limit $start, $limit",
            array(
                        $pack_code
            )
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }

    /**
     * returns the list of recent decklists
     * @param int $start
     * @param int $limit
     * @return array
     */
    public function recent($start = 0, $limit = 30)
    {
        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                c.title outfit,
                p.name lastpack,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                join pack p on d.last_pack_id=p.id
                left join tournament t on d.tournament_id=t.id
                order by creation desc
                limit $start, $limit"
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }



    /**
     * returns a list of decklists according to search criteria
     * @param int $start
     * @param int $limit
     * @param Request $request
     * @return array
     */
    public function find($start = 0, $limit = 30, Request $request)
    {

        $cards_code = $request->query->get('cards');
        $gang_code = filter_var($request->query->get('gang'), FILTER_SANITIZE_STRING);
        $lastpack_code = filter_var($request->query->get('lastpack'), FILTER_SANITIZE_STRING);
        $author_name = filter_var($request->query->get('author'), FILTER_SANITIZE_STRING);
        $decklist_title = filter_var($request->query->get('title'), FILTER_SANITIZE_STRING);
        $sort = $request->query->get('sort');

        $wheres = array();
        $bindings = array();
        if (! empty($gang_code)) {
            $wheres[] = 'f.code=?';
            $bindings[] = $gang_code;
        }
        if (! empty($lastpack_code)) {
            $wheres[] = 'p.code=?';
            $bindings[] = $lastpack_code;
        }
        if (! empty($author_name)) {
            $wheres[] = 'u.username=?';
            $bindings[] = $author_name;
        }
        if (! empty($decklist_title)) {
            $wheres[] = 'd.name like ?';
            $bindings[] = '%' . $decklist_title . '%';
        }
        if (! empty($cards_code) && is_array($cards_code)) {
            foreach ($cards_code as $card_code) {
                $wheres[] = 'exists(select * from decklistslot where decklistslot.decklist_id=d.id and decklistslot.card_id=(select id from card where code=?))';
                $bindings[] = filter_var($card_code, FILTER_SANITIZE_STRING);
            }
        }

        if (empty($wheres)) {
            $where = "1";
            $bindings = array();
        } else {
            $where = implode(" AND ", $wheres);
        }

        switch ($sort) {
            case 'date':
                $order = 'creation';
                break;
            case 'likes':
                $order = 'nbvotes';
                break;
            case 'reputation':
                $order = 'reputation';
                break;
            default:
                $order = 'creation';
        }

        /* @var $dbh PDOConnection */
        $dbh = $this->entityManager->getConnection();

        $rows = $dbh->executeQuery(
            "SELECT SQL_CALC_FOUND_ROWS
                d.id,
                d.name,
                d.prettyname,
                d.creation,
                d.user_id,
                d.tournament_id,
                t.description tournament,
                u.username,
                u.gang usercolor,
                u.reputation,
                u.donation,
                c.code,
                d.nbvotes,
                d.nbfavorites,
                d.nbcomments
                from decklist d
                join user u on d.user_id=u.id
                join card c on d.outfit_id=c.id
                join pack p on d.last_pack_id=p.id
                join gang f on d.gang_id=f.id
                left join tournament t on d.tournament_id=t.id
                where $where
                order by $order desc
                limit $start, $limit",
            $bindings
        )->fetchAll(PDO::FETCH_ASSOC);

        $count = $dbh->executeQuery("SELECT FOUND_ROWS()")->fetch(PDO::FETCH_NUM)[0];

        return array(
                "count" => $count,
                "decklists" => $rows
        );
    }
}
