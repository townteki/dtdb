<?php


namespace Dtdb\BuilderBundle\Services;

use Doctrine\ORM\EntityManager;
use Dtdb\BuilderBundle\Services\Judge;
use Dtdb\BuilderBundle\Entity\Deck;
use Dtdb\BuilderBundle\Entity\Deckslot;
use Dtdb\BuilderBundle\Entity\Deckchange;
use Symfony\Bridge\Monolog\Logger;

class Decks
{
	public function __construct(EntityManager $doctrine, Judge $judge, Diff $diff, Logger $logger) {
		$this->doctrine = $doctrine;
        $this->judge = $judge;
        $this->diff = $diff;
        $this->logger = $logger;
	}
    

    public function getByUser ($user)
    {
        $dbh = $this->doctrine->getConnection();
        $decks = $dbh->executeQuery(
                "SELECT
				d.id,
				d.name,
				DATE_FORMAT(d.datecreation, '%Y-%m-%dT%TZ') datecreation,
                DATE_FORMAT(d.dateupdate, '%Y-%m-%dT%TZ') dateupdate,
				d.description,
                d.tags,
                (select count(*) from deckchange c where c.deck_id=d.id and c.saved=0) unsaved,
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
				order by dateupdate desc", array(
                        $user->getId()
                ))
            ->fetchAll();
        
        $rows = $dbh->executeQuery(
                "SELECT
				s.deck_id,
				c.code card_code,
				s.quantity qty,
                s.start start
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
            $row['start'] = intval($row['start']);
            if (! isset($cards[$deck_id])) {
                $cards[$deck_id] = array();
            }
            $cards[$deck_id][] = $row;
        }
        
        foreach ($decks as $i => $deck) {
            $decks[$i]['cards'] = $cards[$deck['id']];
            $decks[$i]['unsaved'] = intval($decks[$i]['unsaved']);
            $decks[$i]['tags'] = $deck['tags'] ? explode(' ', $deck['tags']) : array();
            $problem_message = '';
            if(isset($deck['problem'])) {
                $problem_message = $this->judge->problem($deck['problem']);
            }
            if($decks[$i]['unsaved'] > 0) {
                $problem_message = "This deck has unsaved changes.";
            }
            
            $decks[$i]['message'] =  $problem_message;
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
				DATE_FORMAT(d.datecreation, '%Y-%m-%dT%TZ') datecreation,
                DATE_FORMAT(d.dateupdate, '%Y-%m-%dT%TZ') dateupdate,
				d.description,
                d.tags,
                (select count(*) from deckchange c where c.deck_id=d.id and c.saved=0) unsaved,
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
				s.quantity qty,
                s.start start
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
            $row['start'] = intval($row['start']);
            $cards[] = $row;
        }
        
        $deck['cards'] = $cards;
        $deck['tags'] = $deck['tags'] ? explode(' ', $deck['tags']) : array();
        $problem = $deck['problem'];
        $deck['message'] = isset($problem) ? $this->judge->problem($problem) : '';
        
        return $deck;
    }
    

    public function saveDeck ($user, $deck, $decklist_id, $name, $description, $tags, $content, $source_deck)
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
        $outfit = null;
        $cards = array();
        /* @var $latestPack \Dtdb\CardsBundle\Entity\Pack */
        $latestPack = null;
        foreach ($content as $card_code => $info) {
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
        $this->doctrine->persist($deck);

        // on the deck content
        
        if($source_deck) {
            $quantities = array_map(function ($value) {
                return $value['quantity'];
            }, $content);
            $this->logger->debug('quantities', $quantities);
            $this->logger->debug('source_content', $source_deck->getContent());
            // compute diff between current content and saved content
            list($listings) = $this->diff->diffContents(array($quantities, $source_deck->getContent()));
            // remove all change (autosave) since last deck update (changes are sorted)
            $changes = $source_deck->getChanges();
            foreach($changes as $change) {
                /* @var $change \Dtdb\BuilderBundler\Entity\Deckchange */
                if(!$change->getSaved()) {
                    $this->doctrine->remove($change);
                } else {
                    break;
                }
            }
            $this->doctrine->flush();
            // save new change unless empty
            if(count($listings[0]) || count($listings[1])) {
                $change = new Deckchange();
                $change->setDeck($deck);
                $change->setVariation(json_encode($listings));
                $change->setSaved(TRUE);
                $this->doctrine->persist($change);
                $this->doctrine->flush();
            }
        }
        foreach ($deck->getSlots() as $slot) {
            $deck->removeSlot($slot);
            $this->doctrine->remove($slot);
        }
         
        foreach ($content as $card_code => $info) {
            $card = $cards[$card_code];
            $card = $cards[$card_code];
            $slot = new Deckslot();
            $slot->setQuantity($info['quantity']);
            $slot->setStart($info['start']);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
            $deck_content[$card_code] = array(
                    'card' => $card,
                    'qty' => $info['quantity'],
                    'start' => $info['start']
            );
        }
        $analyse = $this->judge->analyse($deck_content);
        if (is_string($analyse)) {
            $deck->setProblem($analyse);
        } else {
            $deck->setProblem(NULL);
            $deck->setDeckSize($analyse['deckSize']);
        }
        
        $deck->setDateupdate(new \DateTime());
        $this->doctrine->flush();
        
        return $deck->getId();
    }
    
    public function revertDeck($deck)
    {
        $changes = $deck->getChanges();
        foreach($changes as $change) {
            /* @var $change \Dtdb\BuilderBundler\Entity\Deckchange */
            if(!$change->getSaved()) {
                $this->doctrine->remove($change);
            } else {
                break;
            }
        }
        $this->doctrine->flush();
    }
    
    
}