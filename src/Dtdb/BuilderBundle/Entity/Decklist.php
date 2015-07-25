<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Dtdb\UserBundle\Entity\User;
use Dtdb\BuilderBundle\Entity\Decklistslot;
use Dtdb\BuilderBundle\Entity\Comment;

/**
 * Decklist
 */
class Decklist
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $ts;
    
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $prettyname;
    
    /**
     * @var string
     */
    private $summary;

    /**
     * @var string
     */
    private $rawdescription;
    
    /**
     * @var string
     */
    private $description;

    /**
     * @var \DateTime
     */
    private $creation;
    
    /**
     * @var string
     */
    private $signature;

    /**
     * @var integer
     */
    private $nbvotes;

    /**
     * @var integer
     */
    private $nbfavorites;

    /**
     * @var integer
     */
    private $nbcomments;
    
    /**
     * @var Dtdb\UserBundle\Entity\User
     */
    private $user;

    /**
     * @var Dtdb\CardsBundle\Entity\Card
     */
    private $outfit;

    /**
     * @var Dtdb\CardsBundle\Entity\Gang
     */
    private $gang;
    
    /**
     * @var Dtdb\CardsBundle\Entity\Pack
     */
    private $lastPack;
    
    /**
     * @var Deckslots[]
     */
    private $slots;
    
    /**
     * @var Comments[]
     */
    private $comments;
    
    /**
     * @var User[]
     */
    private $favorites;

    /**
     * @var User[]
     */
    private $votes;
    
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
     * @param \DateTime $ts
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
     * @return \DateTime
     */
    public function getTs()
    {
    	return $this->ts;
    }
    
    /**
     * Set name
     *
     * @param string $name
     * @return List
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
     * @return List
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
     * Set summary
     *
     * @param string $summary
     * @return List
     */
    public function setSummary($summary)
    {
        $this->summary = $summary;
    
        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary()
    {
        return $this->summary;
    }

    /**
     * Set rawdescription
     *
     * @param string $rawdescription
     * @return List
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
     * @return List
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
     * @param \DateTime $creation
     * @return List
     */
    public function setCreation($creation)
    {
        $this->creation = $creation;
    
        return $this;
    }

    /**
     * Get creation
     *
     * @return \DateTime
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
     * @return string
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
     * @return string
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
     * @return string
     */
    public function getNbcomments()
    {
    	return $this->nbcomments;
    }
    
    /**
     * Set user
     *
     * @param string $user
     * @return User
     */
    public function setUser($user)
    {
    	$this->user = $user;
    
    	return $this;
    }
    
    /**
     * Get user
     *
     * @return \Dtdb\UserBundle\Entity\User
     */
    public function getUser()
    {
    	return $this->user;
    }

    /**
     * Set outfit
     *
     * @param \Dtdb\CardsBundle\Entity\Card $outfit
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
     * @return \Dtdb\CardsBundle\Entity\Card
     */
    public function getOutfit()
    {
    	return $this->outfit;
    }
    
    /**
     * Set slots
     *
     * @param string $slots
     * @return Deck
     */
    public function setSlots($slots)
    {
    	$this->slots = $slots;
    
    	return $this;
    }
    
    /**
     * Get slots
     *
     * @return string
     */
    public function getSlots()
    {
    	return $this->slots;
    }

    /**
     * Get cards
     *
     * @return Cards[]
     */
    public function getCards()
    {
    	$arr = array();
    	foreach($this->slots as $slot) {
    		$card = $slot->getCard();
    		$arr[$card->getCode()] = array('qty' => $slot->getQuantity(), 'card' => $card, 'start' => $slot->getStart() );
    	}
    	return $arr;
    }

    /**
     * Set lastPack
     *
     * @param \Dtdb\CardsBundle\Entity\Pack $lastPack
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
     * @return \Dtdb\CardsBundle\Entity\Pack
     */
    public function getLastPack()
    {
    	return $this->lastPack;
    }

    /**
     * Set gang
     *
     * @param \Dtdb\CardsBundle\Entity\Gang $gang
     * @return Deck
     */
    public function setGang($gang)
    {
    	$this->gang = $gang;
    
    	return $this;
    }
    
    /**
     * Get gang
     *
     * @return \Dtdb\CardsBundle\Entity\Gang
     */
    public function getGang()
    {
    	return $this->gang;
    }
    

    /**
     * Set comments
     *
     * @param string $comments
     * @return Deck
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
     * @return User[]
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
     * @return User[]
     */
    public function getVotes()
    {
    	return $this->votes;
    }
    
    public function __construct()
    {
    	$this->slots = new ArrayCollection();
    	$this->comments = new ArrayCollection();
      	$this->favorites = new ArrayCollection();
       	$this->votes = new ArrayCollection();
    }
    
    public function getContent()
    {
    	$arr = array();
    	foreach($this->slots as $slot) {
    		$arr[$slot->getCard()->getCode()] = $slot->getQuantity();
    	}
    	ksort($arr);
    	return $arr;
    }
    /*
    public function getPrettyName()
    {
    	return preg_replace('/[^a-z0-9]+/', '-', mb_strtolower($this->name));
    }
	*/
    /**
     * Add slots
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklistslot $slots
     * @return Decklist
     */
    public function addSlot(\Dtdb\BuilderBundle\Entity\Decklistslot $slots)
    {
        $this->slots[] = $slots;
    
        return $this;
    }

    /**
     * Remove slots
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklistslot $slots
     */
    public function removeSlot(\Dtdb\BuilderBundle\Entity\Decklistslot $slots)
    {
        $this->slots->removeElement($slots);
    }

    /**
     * Add comments
     *
     * @param \Dtdb\BuilderBundle\Entity\Comment $comments
     * @return Decklist
     */
    public function addComment(\Dtdb\BuilderBundle\Entity\Comment $comments)
    {
        $this->comments[] = $comments;
    
        return $this;
    }

    /**
     * Remove comments
     *
     * @param \Dtdb\BuilderBundle\Entity\Comment $comments
     */
    public function removeComment(\Dtdb\BuilderBundle\Entity\Comment $comments)
    {
        $this->comments->removeElement($comments);
    }

    /**
     * Remove favorites
     *
     * @param \Dtdb\UserBundle\Entity\User $favorites
     */
    public function removeFavorite(\Dtdb\UserBundle\Entity\User $favorites)
    {
        $this->favorites->removeElement($favorites);
    }

    /**
     * Remove votes
     *
     * @param \Dtdb\UserBundle\Entity\User $votes
     */
    public function removeVote(\Dtdb\UserBundle\Entity\User $votes)
    {
        $this->votes->removeElement($votes);
    }
    /**
     * @var \Dtdb\BuilderBundle\Entity\Deck
     */
    private $parent;


    /**
     * Set parent
     *
     * @param \Dtdb\BuilderBundle\Entity\Deck $parent
     * @return Decklist
     */
    public function setParent(\Dtdb\BuilderBundle\Entity\Deck $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \Dtdb\BuilderBundle\Entity\Deck
     */
    public function getParent()
    {
        return $this->parent;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $successors;

    /**
     * @var \Dtdb\BuilderBundle\Entity\Decklist
     */
    private $precedent;


    /**
     * Add successors
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $successors
     * @return Decklist
     */
    public function addSuccessor(\Dtdb\BuilderBundle\Entity\Decklist $successors)
    {
        $this->successors[] = $successors;
    
        return $this;
    }

    /**
     * Remove successors
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $successors
     */
    public function removeSuccessor(\Dtdb\BuilderBundle\Entity\Decklist $successors)
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
     * @param \Dtdb\BuilderBundle\Entity\Decklist $precedent
     * @return Decklist
     */
    public function setPrecedent(\Dtdb\BuilderBundle\Entity\Decklist $precedent = null)
    {
        $this->precedent = $precedent;
    
        return $this;
    }

    /**
     * Get precedent
     *
     * @return \Dtdb\BuilderBundle\Entity\Decklist
     */
    public function getPrecedent()
    {
        return $this->precedent;
    }
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;


    /**
     * Add children
     *
     * @param \Dtdb\BuilderBundle\Entity\Deck $children
     * @return Decklist
     */
    public function addChildren(\Dtdb\BuilderBundle\Entity\Deck $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \Dtdb\BuilderBundle\Entity\Deck $children
     */
    public function removeChildren(\Dtdb\BuilderBundle\Entity\Deck $children)
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
     * @param \Dtdb\BuilderBundle\Entity\Deck $children
     * @return Decklist
     */
    public function addChild(\Dtdb\BuilderBundle\Entity\Deck $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Dtdb\BuilderBundle\Entity\Deck $children
     */
    public function removeChild(\Dtdb\BuilderBundle\Entity\Deck $children)
    {
        $this->children->removeElement($children);
    }

    /**
     * @var \Dtdb\BuilderBundle\Entity\Tournament
     */
    private $tournament;

	/**
	 * Set tournament
	 *
	 * @param \Dtdb\BuilderBundle\Entity\Tournament $tournament
	 * @return Decklist
	 */
	public function setTournament(\Dtdb\BuilderBundle\Entity\Tournament $tournament = null)
	{
		$this->tournament = $tournament;
	
		return $this;
	}
	
	/**
	 * Get tournament
	 *
	 * @return \Dtdb\BuilderBundle\Entity\Tournament
	 */
	public function getTournament()
	{
		return $this->tournament;
	}
	
}
