<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(
 *     name="decklist",
 *     indexes={
 *         @ORM\Index(name="creation_index", columns={"creation"})
 *     }
 * )
 */
class Decklist
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
     * @var DateTime
     *
     * @ORM\Column(name="ts", type="datetime")
     */
    protected $ts;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=60)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="prettyname", type="string", length=60)
     */
    protected $prettyname;

    /**
     * @var string|null
     *
     * @ORM\Column(name="rawdescription", type="text", nullable=true)
     */
    protected $rawdescription;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     */
    protected $description;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime")
     */
    protected $creation;

    /**
     * @var string
     *
     * @ORM\Column(name="signature", type="string", length=32)
     */
    protected $signature;

    /**
     * @var int
     *
     * @ORM\Column(name="nbvotes", type="integer")
     */
    protected $nbvotes;

    /**
     * @var int
     *
     * @ORM\Column(name="nbfavorites", type="integer")
     */
    protected $nbfavorites;

    /**
     * @var int
     *
     * @ORM\Column(name="nbcomments", type="integer")
     */
    protected $nbcomments;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklistslot", mappedBy="decklist", cascade={"persist","remove"})
     */
    protected $slots;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="decklist", cascade={"persist","remove"})
     */
    protected $comments;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="precedent")
     */
    protected $successors;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deck", mappedBy="parent")
     */
    protected $children;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="decklists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     * })
     */
    protected $user;

    /**
     * @var Card
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Card", inversedBy="decklists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="outfit_id", referencedColumnName="id")
     * })
     */
    protected $outfit;

    /**
     * @var Gang
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Gang", inversedBy="decklists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="gang_id", referencedColumnName="id")
     * })
     */
    protected $gang;

    /**
     * @var Pack
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Pack", inversedBy="decklists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="last_pack_id", referencedColumnName="id")
     * })
     */
    protected $lastPack;

    /**
     * @var Deck
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="children")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="parent_deck_id", referencedColumnName="id")
     * })
     */
    protected $parent;

    /**
     * @var Decklist
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Decklist", inversedBy="successors")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="precedent_decklist_id", referencedColumnName="id")
     * })
     */
    protected $precedent;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="favorites", cascade={"persist"})
     * @ORM\JoinTable(name="favorite",
     *   joinColumns={
     *     @ORM\JoinColumn(name="decklist_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   }
     * )
     */
    protected $favorites;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="votes", cascade={"persist"})
     * @ORM\JoinTable(name="vote",
     *   joinColumns={
     *     @ORM\JoinColumn(name="decklist_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   }
     * )
     */
    protected $votes;

    /**
     * @var Tournament
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Tournament", inversedBy="decklists")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="tournament_id", referencedColumnName="id")
     * })
     */
    protected $tournament;


    /**
     * @var boolean isWwe;
     * @ORM\Column(name="is_wwe", type="boolean", nullable=false,  options={"default"="0"})
     */
    protected $isWwe;

    public function __construct()
    {
        $this->slots = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->successors = new ArrayCollection();
        $this->children = new ArrayCollection();
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
     * Set ts
     *
     * @param DateTime $ts
     * @return Decklist
     */
    public function setTs($ts)
    {
         $this->ts = $ts;

         return $this;
    }

    /**
     * Get ts
     *
     * @return DateTime
     */
    public function getTs()
    {
         return $this->ts;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Decklist
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
     * Set prettyname
     *
     * @param string $prettyname
     * @return Decklist
     */
    public function setPrettyname($prettyname)
    {
         $this->prettyname = $prettyname;

         return $this;
    }

    /**
     * Get prettyname
     *
     * @return string
     */
    public function getPrettyname()
    {
         return $this->prettyname;
    }

    /**
     * Set rawdescription
     *
     * @param string $rawdescription
     * @return Decklist
     */
    public function setRawdescription($rawdescription)
    {
         $this->rawdescription = $rawdescription;

         return $this;
    }

    /**
     * Get rawdescription
     *
     * @return string
     */
    public function getRawdescription()
    {
         return $this->rawdescription;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Decklist
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
     * Set creation
     *
     * @param DateTime $creation
     * @return Decklist
     */
    public function setCreation($creation)
    {
        $this->creation = $creation;

        return $this;
    }

    /**
     * Get creation
     *
     * @return DateTime
     */
    public function getCreation()
    {
        return $this->creation;
    }

    /**
     * Set signature
     *
     * @param string $signature
     * @return Decklist
     */
    public function setSignature($signature)
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature
     *
     * @return string
     */
    public function getSignature()
    {
        return $this->signature;
    }

    /**
     * Set nbvotes
     *
     * @param string $nbvotes
     * @return Decklist
     */
    public function setNbvotes($nbvotes)
    {
         $this->nbvotes = $nbvotes;

         return $this;
    }

    /**
     * Get nbvotes
     *
     * @return int
     */
    public function getNbvotes()
    {
         return $this->nbvotes;
    }

    /**
     * Set nbfavorites
     *
     * @param string $nbfavorites
     * @return Decklist
     */
    public function setNbfavorites($nbfavorites)
    {
         $this->nbfavorites = $nbfavorites;

         return $this;
    }

    /**
     * Get nbfavorites
     *
     * @return int
     */
    public function getNbfavorites()
    {
         return $this->nbfavorites;
    }

    /**
     * Set nbcomments
     *
     * @param string $nbcomments
     * @return Decklist
     */
    public function setNbcomments($nbcomments)
    {
         $this->nbcomments = $nbcomments;

         return $this;
    }

    /**
     * Get nbcomments
     *
     * @return int
     */
    public function getNbcomments()
    {
         return $this->nbcomments;
    }

    /**
     * Set user
     *
     * @param string $user
     * @return Decklist
     */
    public function setUser($user)
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
     * @return Decklist
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
     * Set slots
     *
     * @param string $slots
     * @return Decklist
     */
    public function setSlots($slots)
    {
         $this->slots = $slots;

         return $this;
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

    /**
     * Set lastPack
     *
     * @param Pack $lastPack
     * @return Decklist
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
     * Set gang
     *
     * @param Gang $gang
     * @return Decklist
     */
    public function setGang($gang)
    {
         $this->gang = $gang;

         return $this;
    }

    /**
     * Get gang
     *
     * @return Gang
     */
    public function getGang()
    {
         return $this->gang;
    }


    /**
     * Set comments
     *
     * @param string $comments
     * @return Decklist
     */
    public function setComments($comments)
    {
         $this->comments = $comments;

         return $this;
    }

    /**
     * Get comments
     *
     * @return string
     */
    public function getComments()
    {
         return $this->comments;
    }

    /**
     * Add favorite
     *
     * @param User $user
     * @return Decklist
     */
    public function addFavorite($user)
    {
         $this->favorites[] = $user;

         return $this;
    }

    /**
     * Get favorites
     *
     * @return Collection
     */
    public function getFavorites()
    {
         return $this->favorites;
    }

    /**
     * Add vote
     *
     * @param User $user
     * @return Decklist
     */
    public function addVote($user)
    {
         $this->votes[] = $user;

         return $this;
    }

    /**
     * Get votes
     *
     * @return Collection
     */
    public function getVotes()
    {
         return $this->votes;
    }

    /**
     * @return array
     */
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
     * Add slots
     *
     * @param Decklistslot $slots
     * @return Decklist
     */
    public function addSlot(Decklistslot $slots)
    {
        $this->slots[] = $slots;

        return $this;
    }

    /**
     * Remove slots
     *
     * @param Decklistslot $slots
     */
    public function removeSlot(Decklistslot $slots)
    {
        $this->slots->removeElement($slots);
    }

    /**
     * Add comments
     *
     * @param Comment $comments
     * @return Decklist
     */
    public function addComment(Comment $comments)
    {
        $this->comments[] = $comments;

        return $this;
    }

    /**
     * Remove comments
     *
     * @param Comment $comments
     */
    public function removeComment(Comment $comments)
    {
        $this->comments->removeElement($comments);
    }

    /**
     * Remove favorites
     *
     * @param User $favorites
     */
    public function removeFavorite(User $favorites)
    {
        $this->favorites->removeElement($favorites);
    }

    /**
     * Remove votes
     *
     * @param User $votes
     */
    public function removeVote(User $votes)
    {
        $this->votes->removeElement($votes);
    }


    /**
     * Set parent
     *
     * @param Deck|null $parent
     * @return Decklist
     */
    public function setParent(Deck $parent = null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent
     *
     * @return Deck
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add successors
     *
     * @param Decklist $successors
     * @return Decklist
     */
    public function addSuccessor(Decklist $successors)
    {
        $this->successors[] = $successors;

        return $this;
    }

    /**
     * Remove successors
     *
     * @param Decklist $successors
     */
    public function removeSuccessor(Decklist $successors)
    {
        $this->successors->removeElement($successors);
    }

    /**
     * Get successors
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getSuccessors()
    {
        return $this->successors;
    }

    /**
     * Set precedent
     *
     * @param Decklist $precedent
     * @return Decklist
     */
    public function setPrecedent(Decklist $precedent = null)
    {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent
     *
     * @return Decklist
     */
    public function getPrecedent()
    {
        return $this->precedent;
    }

    /**
     * Add children
     *
     * @param Deck $children
     * @return Decklist
     */
    public function addChildren(Deck $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param Deck $children
     */
    public function removeChildren(Deck $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Get children
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * Add children
     *
     * @param Deck $children
     * @return Decklist
     */
    public function addChild(Deck $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param Deck $children
     */
    public function removeChild(Deck $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * Set tournament
     *
     * @param Tournament $tournament
     * @return Decklist
     */
    public function setTournament(Tournament $tournament = null)
    {
         $this->tournament = $tournament;

         return $this;
    }

    /**
     * Get tournament
     *
     * @return Tournament
     */
    public function getTournament()
    {
         return $this->tournament;
    }

    /**
     * @param bool $isWwe
     * @return $this
     */
    public function setIsWwe(bool $isWwe) {
        $this->isWwe = $isWwe;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsWwe() {
        return $this->isWwe;
    }
}
