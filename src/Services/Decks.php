<?php

namespace App\Services;

use App\Entity\Card;
use App\Entity\Decklist;
use App\Entity\Deckslot;
use App\Entity\Deckchange;
use App\Entity\Pack;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class Decks
{
    protected EntityManagerInterface $entityManager;
    protected Judge $judge;
    protected Diff $diff;
    protected LoggerInterface $logger;

    // TCaR (There Comes a Reckoning) is a first expansion published by PBE
    public const TCAR_CYCLENUMBER = 11;
    public const TCAR_NUMBER = 1;

    public const FORMAT_TAG_OLDTIMER = 'oldtimer';
    public const FORMAT_TAG_WWE = 'wwe';

    public function __construct(
        EntityManagerInterface $entityManager,
        Judge $judge,
        Diff $diff,
        LoggerInterface $logger
    ) {
        $this->entityManager = $entityManager;
        $this->judge = $judge;
        $this->diff = $diff;
        $this->logger = $logger;
    }


    public function getByUser($user)
    {
        $dbh = $this->entityManager->getConnection();
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
                                (select title from card
                                    inner join deckslot on card.id = deckslot.card_id
                                    where deck_id = d.id
                                    and card_id in
                                        (select id from card where type_id IN
                                         (select ID from type where name = 'Legend')) limit 1) legend_title,
                                (select code from card
                                    inner join deckslot on card.id = deckslot.card_id
                                    where deck_id = d.id
                                    and card_id in
                                        (select id from card where type_id IN
                                            (select ID from type where name = 'Legend')) limit 1) legend_code,
                g.code gang_code,
                p.cycle_id cycle_id,
                p.number pack_number
                from deck d
                left join card c on d.outfit_id=c.id
                left join gang g on c.gang_id=g.id
                left join pack p on d.last_pack_id=p.id
                where d.user_id=?
                order by dateupdate desc",
            array(
                        $user->getId()
            )
        )
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
                where d.user_id=?",
            array(
                        $user->getId()
            )
        )
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
            if (isset($deck['problem'])) {
                $problem_message = $this->judge->problem($deck['problem']);
            }
            if ($decks[$i]['unsaved'] > 0) {
                $problem_message = "This deck has unsaved changes.";
            }

            $decks[$i]['message'] =  $problem_message;
        }

        return $decks;
    }

    public function getById($deck_id)
    {
        $dbh = $this->entityManager->getConnection();
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
                where d.id=?",
            array(
                        $deck_id
            )
        )
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
                where d.id=?",
            array(
                        $deck_id
            )
        )
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


    public function saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, $source_deck)
    {
        $deck_content = array();

        if ($decklist_id) {
            $decklist = $this->entityManager->getRepository(Decklist::class)->find($decklist_id);
            if ($decklist) {
                $deck->setParent($decklist);
            }
        }

        $deck->setName($name);
        $deck->setDescription($description);
        $deck->setUser($user);
        $outfit = null;
        $cards = array();
        /* @var Pack $latestPack */
        $latestPack = null;
        $earliestPack = null;
        foreach ($content as $card_code => $info) {
            $card = $this->entityManager->getRepository(Card::class)->findOneBy(array(
                    "code" => $card_code
            ));
            if (!$card) {
                continue;
            }
            $pack = $card->getPack();
            if (! $latestPack) {
                $latestPack = $pack;
            } elseif ($latestPack->getCycle()->getNumber() < $pack->getCycle()->getNumber()) {
                $latestPack = $pack;
            } elseif ($latestPack->getCycle()->getNumber() == $pack->getCycle()->getNumber() && $latestPack->getNumber() < $pack->getNumber()) {
                $latestPack = $pack;
            }
            if (! $earliestPack) {
                $earliestPack = $pack;
            } elseif ($earliestPack->getCycle()->getNumber() > $pack->getCycle()->getNumber()) {
                $earliestPack = $pack;
            } elseif ($earliestPack->getCycle()->getNumber() == $pack->getCycle()->getNumber() && $earliestPack->getNumber() > $pack->getNumber()) {
                $earliestPack = $pack;
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
            $outfit = $this->entityManager->getRepository(Card::class)->findAll();
            $cards[$outfit->getCode()] = $outfit;
            $content[$outfit->getCode()] = 1;
            $deck->setOutfit($outfit);
        }
        if (empty($tags)) {
            // tags can never be empty. if it is we put gang in
            $tags = $outfit->getGangCodes();
        } elseif (!is_array($tags)) {
            $tags = explode(' ', $tags);
        }

        // remove any pre-existing format tags
        $tags = array_diff($tags, [self::FORMAT_TAG_OLDTIMER, self::FORMAT_TAG_WWE]);

        // TCaR is used to distinguish between WWE (standard) format and Old Timer (legacy) format
        $formattag = self::FORMAT_TAG_OLDTIMER;
        if (
            $earliestPack->getCycle()->getNumber() > self::TCAR_CYCLENUMBER
            || ($earliestPack->getCycle()->getNumber() == self::TCAR_CYCLENUMBER
                && $earliestPack->getNumber() >= self::TCAR_NUMBER
            )
        ) {
            $formattag = self::FORMAT_TAG_WWE;
        }
        $tags[] = $formattag;

        $tags = implode(' ', $tags);
        $deck->setTags($tags);
        $this->entityManager->persist($deck);

        // on the deck content

        if ($source_deck) {
            $quantities = array_map(function ($value) {
                return $value['quantity'];
            }, $content);
            $this->logger->debug('quantities', $quantities);
            $this->logger->debug('source_content', $source_deck->getContent());
            // compute diff between current content and saved content
            list($listings) = $this->diff->diffContents(array($quantities, $source_deck->getContent()));
            // remove all change (autosave) since last deck update (changes are sorted)
            $changes = $source_deck->getChanges();
            foreach ($changes as $change) {
                /* @var Deckchange $change */
                if (!$change->getSaved()) {
                    $this->entityManager->remove($change);
                } else {
                    break;
                }
            }
            $this->entityManager->flush();
            // save new change unless empty
            if (count($listings[0]) || count($listings[1])) {
                $change = new Deckchange();
                $change->setDeck($deck);
                $change->setVariation(json_encode($listings));
                $change->setSaved(true);
                $this->entityManager->persist($change);
                $this->entityManager->flush();
            }
        }
        foreach ($deck->getSlots() as $slot) {
            $deck->removeSlot($slot);
            $this->entityManager->remove($slot);
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
            $deck->setProblem(null);
            $deck->setDeckSize($analyse['deckSize']);
        }

        $deck->setDateupdate(new \DateTime());
        $this->entityManager->flush();

        return $deck->getId();
    }

    public function revertDeck($deck)
    {
        $changes = $deck->getChanges();
        foreach ($changes as $change) {
            /* @var Deckchange $change  */
            if (!$change->getSaved()) {
                $this->entityManager->remove($change);
            } else {
                break;
            }
        }
        $this->entityManager->flush();
    }
}
