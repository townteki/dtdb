<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="deck",
 *     indexes={
 *         @ORM\Index(name="dateupdate_index", columns={"dateupdate"})
 *     }
 * )
 */
class Deck
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    protected $name;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="datecreation", type="datetime", nullable=false)
     */
    protected $datecreation;

    /**
     * @var DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="dateupdate", type="datetime", nullable=false)
     */
    protected $dateupdate;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var string|null
     *
     * @ORM\Column(name="problem", type="string", length=20, nullable=true)
     */
    protected $problem;

    /**
     * @var int|null
     *
     * @ORM\Column(name="deck_size", type="smallint", nullable=true)
     */
    protected $deckSize;

    /**
     * @var string|null
     *
     * @ORM\Column(name="tags", type="string", length=4000, nullable=true)
     */
    protected $tags;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deckslot", mappedBy="deck", cascade={"persist","remove"})
     */
    protected $slots;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="parent")
     * @ORM\OrderBy({
     *     "creation"="DESC"
     * })
     */
    protected $children;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deckchange", mappedBy="deck", cascade={"persist","remove"})
     * @ORM\OrderBy({
     *     "datecreation"="DESC",
     * })
     */
    protected $changes;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="decks")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    protected $user;

    /**
     * @var Card
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Card")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="outfit_id", referencedColumnName="id")
     * })
     */
    protected $outfit;

    /**
     * @var Pack
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Pack")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="last_pack_id", referencedColumnName="id")
     * })
     */
    protected $lastPack;

    /**
     * @var Decklist
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Decklist", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_decklist_id", referencedColumnName="id")
     * })
     */
    protected $parent;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->changes = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Deck
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set datecreation
     *
     * @param DateTime $datecreation
     * @return Deck
     */
    public function setDatecreation($datecreation)
    {
        $this->datecreation = $datecreation;

        return $this;
    }

    /**
     * Get datecreation
     *
     * @return DateTime
     */
    public function getDatecreation()
    {
        return $this->datecreation;
    }

    /**
     * Set dateupdate
     *
     * @param DateTime $dateupdate
     * @return Deck
     */
    public function setDateupdate($dateupdate)
    {
        $this->dateupdate = $dateupdate;

        return $this;
    }

    /**
     * Get dateupdate
     *
     * @return DateTime
     */
    public function getDateupdate()
    {
        return $this->dateupdate;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Deck
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set problem
     *
     * @param string $problem
     * @return Deck
     */
    public function setProblem($problem)
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem
     *
     * @return string
     */
    public function getProblem()
    {
        return $this->problem;
    }

    /**
     * Add slots
     *
     * @param Deckslot $slots
     * @return Deck
     */
    public function addSlot(Deckslot $slots)
    {
        $this->slots[] = $slots;

        return $this;
    }

    /**
     * Remove slots
     *
     * @param Deckslot $slots
     */
    public function removeSlot(Deckslot $slots)
    {
        $this->slots->removeElement($slots);
    }

    /**
     * Get slots
     *
     * @return Collection
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * Set user
     *
     * @param User|null $user
     * @return Deck
     */
    public function setUser(User $user = null)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set outfit
     *
     * @param Card $outfit
     * @return Deck
     */
    public function setOutfit($outfit)
    {
        $this->outfit = $outfit;

        return $this;
    }

    /**
     * Get outfit
     *
     * @return Card
     */
    public function getOutfit()
    {
        return $this->outfit;
    }

    /**
     * Set lastPack
     *
     * @param Pack $lastPack
     * @return Deck
     */
    public function setLastPack($lastPack)
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack
     *
     * @return Pack
     */
    public function getLastPack()
    {
        return $this->lastPack;
    }

    /**
     * Set deckSize
     *
     * @param integer $deckSize
     * @return Deck
     */
    public function setDeckSize($deckSize)
    {
        $this->deckSize = $deckSize;

        return $this;
    }

    /**
     * Get deckSize
     *
     * @return integer
     */
    public function getDeckSize()
    {
        return $this->deckSize;
    }

    /**
     * Set tags
     *
     * @param string $tags
     * @return Deck
     */
    public function setTags($tags)
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags
     *
     * @return string
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Get cards
     *
     * @return Card[]
     */
    public function getCards()
    {
        $arr = array();
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            $arr[$card->getCode()] = array(
                'qty' => $slot->getQuantity(),
                'card' => $card,
                'start' => $slot->getStart()
            );
        }
        return $arr;
    }

    public function getContent()
    {
        $arr = array();
        foreach ($this->slots as $slot) {
            $arr[$slot->getCard()->getCode()] = $slot->getQuantity();
        }
        ksort($arr);
        return $arr;
    }

    /**
     * Add children
     *
     * @param Decklist $children
     * @return Deck
     */
    public function addChildren(Decklist $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param Decklist $children
     */
    public function removeChildren(Decklist $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Set parent
     *
     * @param Decklist|null $parent
     * @return Deck
     */
    public function setParent(Decklist $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Decklist
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param Decklist $children
     * @return Deck
     */
    public function addChild(Decklist $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param Decklist $children
     */
    public function removeChild(Decklist $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Add changes
     *
     * @param Deckchange $changes
     * @return Deck
     */
    public function addChange(Deckchange $changes)
    {
        $this->changes[] = $changes;

        return $this;
    }

    /**
     * Remove changes
     *
     * @param Deckchange $changes
     */
    public function removeChange(Deckchange $changes)
    {
        $this->changes->removeElement($changes);
    }

    /**
     * Get changes
     *
     * @return Collection
     */
    public function getChanges()
    {
        return $this->changes;
    }
}
