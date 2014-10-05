<?php


namespace Dtdb\BuilderBundle\Services;

use Doctrine\ORM\EntityManager;
use Dtdb\BuilderBundle\Services\Judge;
use Dtdb\BuilderBundle\Entity\Deck;
use Dtdb\BuilderBundle\Entity\Deckslot;

class Decks
{
	public function __construct(EntityManager $doctrine, Judge $judge) {
		$this->doctrine = $doctrine;
        $this->judge = $judge;
	}
    

    public function getByUser ($user)
    {
        $dbh = $this->doctrine->getConnection();
        $decks = $dbh->executeQuery(
                "SELECT
				d.id,
				d.name,
				d.creation,
                d.lastupdate,
				d.description,
                d.tags,
				d.problem,
				c.title outfit_title,
                c.code outfit_code,
				g.code gang_code,
                p.cycle_id cycle_id,
                p.number pack_number
				from deck d
				left join card c on d.outfit_id=c.id
				left join gang g on c.gang_id=g.id
                left join pack p on d.last_pack_id=p.id
				where d.user_id=?
				order by lastupdate desc", array(
                        $user->getId()
                ))
            ->fetchAll();
        
        $rows = $dbh->executeQuery(
                "SELECT
				s.deck_id,
				c.code card_code,
				s.quantity qty
				from deckslot s
				join card c on s.card_id=c.id
				join deck d on s.deck_id=d.id
				where d.user_id=?", array(
                        $user->getId()
                ))
            ->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $deck_id = $row['deck_id'];
            unset($row['deck_id']);
            $row['qty'] = intval($row['qty']);
            if (! isset($cards[$deck_id])) {
                $cards[$deck_id] = array();
            }
            $cards[$deck_id][] = $row;
        }
        
        foreach ($decks as $i => $deck) {
            $decks[$i]['cards'] = $cards[$deck['id']];
            $decks[$i]['tags'] = $deck['tags'] ? explode(' ', $deck['tags']) : array();
            $problem = $deck['problem'];
            $decks[$i]['message'] = isset($problem) ? $this->judge->problem($problem) : '';
        }
        
        return $decks;
    
    }

    public function getById ($deck_id)
    {
        $dbh = $this->doctrine->getConnection();
        $deck = $dbh->executeQuery(
                "SELECT
				d.id,
				d.name,
				d.creation,
				d.description,
                d.tags,
				d.problem,
				c.title outfit_title,
                c.code outfit_code,
				f.code gang_code
				from deck d
				left join card c on d.outfit_id=c.id
				left join gang f on c.gang_id=f.id
				where d.id=?", array(
                        $deck_id
                ))
            ->fetch();
        
        $rows = $dbh->executeQuery(
                "SELECT
				s.deck_id,
				c.code card_code,
				s.quantity qty
				from deckslot s
				join card c on s.card_id=c.id
				join deck d on s.deck_id=d.id
				where d.id=?", array(
                        $deck_id
                ))
            ->fetchAll();
        
        $cards = array();
        foreach ($rows as $row) {
            $deck_id = $row['deck_id'];
            unset($row['deck_id']);
            $row['qty'] = intval($row['qty']);
            $cards[] = $row;
        }
        
        $deck['cards'] = $cards;
        $deck['tags'] = $deck['tags'] ? explode(' ', $deck['tags']) : array();
        $problem = $deck['problem'];
        $deck['message'] = isset($problem) ? $this->judge->problem($problem) : '';
        
        return $deck;
    }
    

    public function save ($user, $deck, $decklist_id, $name, $description, $tags, $content)
    {
        $deck_content = array();
        
        if ($decklist_id) {
            $decklist = $this->doctrine->getRepository('DtdbBuilderBundle:Decklist')->find($decklist_id);
            if ($decklist)
                $deck->setParent($decklist);
        }
        
        $deck->setName($name);
        $deck->setDescription($description);
        $deck->setUser($user);
        if (! $deck->getCreation()) {
            $deck->setCreation(new \DateTime());
        }
        $deck->setLastupdate(new \DateTime());
        $outfit = null;
        $cards = array();
        /* @var $latestPack \Dtdb\CardsBundle\Entity\Pack */
        $latestPack = null;
        foreach ($content as $card_code => $qty) {
            $card = $this->doctrine->getRepository('DtdbCardsBundle:Card')->findOneBy(array(
                    "code" => $card_code
            ));
            if(!$card) continue;
            $pack = $card->getPack();
            if (! $latestPack) {
                $latestPack = $pack;
            } else
                if ($latestPack->getCycle()->getNumber() < $pack->getCycle()->getNumber()) {
                    $latestPack = $pack;
                } else
                    if ($latestPack->getCycle()->getNumber() == $pack->getCycle()->getNumber() && $latestPack->getNumber() < $pack->getNumber()) {
                        $latestPack = $pack;
                    }
            if ($card->getType()->getName() == "Outfit") {
                $outfit = $card;
            }
            $cards[$card_code] = $card;
        }
        $deck->setLastPack($latestPack);
        if ($outfit) {
            $deck->setOutfit($outfit);
        } else {
            $outfit = $this->doctrine->getRepository('DtdbCardsBundle:Card')->findAll();
            $cards[$outfit->getCode()] = $outfit;
            $content[$outfit->getCode()] = 1;
            $deck->setOutfit($outfit);
        }
        if(empty($tags)) {
            // tags can never be empty. if it is we put gang in
            $gang_code = $outfit->getGang()->getCode();
            $tags = array($gang_code);
        }
        if(is_array($tags)) {
            $tags = implode(' ', $tags);
        }
        $deck->setTags($tags);
        foreach ($content as $card_code => $qty) {
            $card = $cards[$card_code];
            $card = $cards[$card_code];
            $slot = new Deckslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
            $deck_content[$card_code] = array(
                    'card' => $card,
                    'qty' => $qty
            );
        }
        $analyse = $this->judge->analyse($deck_content);
        if (is_string($analyse)) {
            $deck->setProblem($analyse);
        } else {
            $deck->setProblem(NULL);
            $deck->setDeckSize($analyse['deckSize']);
        }
        
        $this->doctrine->persist($deck);
        $this->doctrine->flush();
        
        return $deck->getId();
    }
    
    
}