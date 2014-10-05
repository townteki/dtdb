<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Deck
 */
class Deck
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $creation;

    /**
     * @var \DateTime
     */
    private $lastupdate;

    /**
     * @var string
     */
    private $description;
    
    /**
     * @var string
     */
    private $problem;
    
    /**
     * @var integer
     */
    private $deckSize;

    /**
     * @var integer
     */
    private $influenceSpent;

    /**
     * @var integer
     */
    private $agendaPoints;

    /**
     * @var string
     */
    private $tags;
    
    private $message;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Dtdb\UserBundle\Entity\User
     */
    private $user;

    /**
     * @var \Dtdb\CardsBundle\Entity\Side
     */
    private $side;

    /**
     * @var Dtdb\CardsBundle\Entity\Card
     */
    private $outfit;
    
    /**
     * @var Dtdb\CardsBundle\Entity\Pack
     */
    private $lastPack;
    
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->slots = new \Doctrine\Common\Collections\ArrayCollection();
        $this->descendants = new \Doctrine\Common\Collections\ArrayCollection();
        $this->children = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set creation
     *
     * @param \DateTime $creation
     * @return Deck
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
     * Set lastupdate
     *
     * @param \DateTime $lastupdate
     * @return Deck
     */
    public function setLastupdate($lastupdate)
    {
        $this->lastupdate = $lastupdate;
    
        return $this;
    }

    /**
     * Get lastupdate
     *
     * @return \DateTime 
     */
    public function getLastupdate()
    {
        return $this->lastupdate;
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
     * @param \Dtdb\BuilderBundle\Entity\Deckslot $slots
     * @return Deck
     */
    public function addSlot(\Dtdb\BuilderBundle\Entity\Deckslot $slots)
    {
        $this->slots[] = $slots;
    
        return $this;
    }

    /**
     * Remove slots
     *
     * @param \Dtdb\BuilderBundle\Entity\Deckslot $slots
     */
    public function removeSlot(\Dtdb\BuilderBundle\Entity\Deckslot $slots)
    {
        $this->slots->removeElement($slots);
    }

    /**
     * Get slots
     *
     * @return \Dtdb\BuilderBundle\Entity\Deckslot[] 
     */
    public function getSlots()
    {
        return $this->slots;
    }

    /**
     * Set user
     *
     * @param \Dtdb\UserBundle\Entity\User $user
     * @return Deck
     */
    public function setUser(\Dtdb\UserBundle\Entity\User $user = null)
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
     * Set side
     *
     * @param \Dtdb\CardsBundle\Entity\Side $side
     * @return Deck
     */
    public function setSide(\Dtdb\CardsBundle\Entity\Side $side = null)
    {
        $this->side = $side;
    
        return $this;
    }

    /**
     * Get side
     *
     * @return \Dtdb\CardsBundle\Entity\Side 
     */
    public function getSide()
    {
        return $this->side;
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
     * Set influenceSpent
     *
     * @param integer $influenceSpent
     * @return Deck
     */
    public function setInfluenceSpent($influenceSpent)
    {
        $this->influenceSpent = $influenceSpent;
    
        return $this;
    }

    /**
     * Get influenceSpent
     *
     * @return integer 
     */
    public function getInfluenceSpent()
    {
        return $this->influenceSpent;
    }

    /**
     * Set agendaPoints
     *
     * @param integer $agendaPoints
     * @return Deck
     */
    public function setAgendaPoints($agendaPoints)
    {
        $this->agendaPoints = $agendaPoints;
    
        return $this;
    }

    /**
     * Get agendaPoints
     *
     * @return integer 
     */
    public function getAgendaPoints()
    {
        return $this->agendaPoints;
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
     * @return Cards[]
     */
    public function getCards()
    {
    	$arr = array();
    	foreach($this->slots as $slot) {
    		$card = $slot->getCard();
    		$arr[$card->getCode()] = array('qty' => $slot->getQuantity(), 'card' => $card);
    	}
    	return $arr;
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
    
    public function getMessage()
    {
    	return $this->message;
    }
    
    public function setMessage($message)
    {
    	$this->message = $message;
    	return $this;
    }
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $children;

    /**
     * @var \Dtdb\BuilderBundle\Entity\Decklist
     */
    private $parent;


    /**
     * Add children
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $children
     * @return Deck
     */
    public function addChildren(\Dtdb\BuilderBundle\Entity\Decklist $children)
    {
        $this->children[] = $children;
    
        return $this;
    }

    /**
     * Remove children
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $children
     */
    public function removeChildren(\Dtdb\BuilderBundle\Entity\Decklist $children)
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
     * Set parent
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $parent
     * @return Deck
     */
    public function setParent(\Dtdb\BuilderBundle\Entity\Decklist $parent = null)
    {
        $this->parent = $parent;
    
        return $this;
    }

    /**
     * Get parent
     *
     * @return \Dtdb\BuilderBundle\Entity\Decklist 
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Add children
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $children
     * @return Deck
     */
    public function addChild(\Dtdb\BuilderBundle\Entity\Decklist $children)
    {
        $this->children[] = $children;

        return $this;
    }

    /**
     * Remove children
     *
     * @param \Dtdb\BuilderBundle\Entity\Decklist $children
     */
    public function removeChild(\Dtdb\BuilderBundle\Entity\Decklist $children)
    {
        $this->children->removeElement($children);
    }
}
