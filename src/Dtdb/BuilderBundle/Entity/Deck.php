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
    private $datecreation;

    /**
     * @var \DateTime
     */
    private $dateupdate;

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
     * @var string
     */
    private $tags;
    
    private $message;
    
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $slots;

    /**
     * @var \Dtdb\BuilderBundle\Entity\User
     */
    private $user;

    /**
     * @var Dtdb\BuilderBundle\Entity\Card
     */
    private $outfit;
    
    /**
     * @var Dtdb\BuilderBundle\Entity\Pack
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
     * Set datecreation
     *
     * @param \DateTime $datecreation
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
     * @return \DateTime
     */
    public function getDatecreation()
    {
        return $this->datecreation;
    }

    /**
     * Set dateupdate
     *
     * @param \DateTime $dateupdate
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
     * @return \DateTime
     */
    public function getDateupdate()
    {
        return $this->dateupdate;
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
     * @param \Dtdb\BuilderBundle\Entity\User $user
     * @return Deck
     */
    public function setUser(\Dtdb\BuilderBundle\Entity\User $user = null)
    {
        $this->user = $user;
    
        return $this;
    }

    /**
     * Get user
     *
     * @return \Dtdb\BuilderBundle\Entity\User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Set outfit
     *
     * @param \Dtdb\BuilderBundle\Entity\Card $outfit
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
     * @return \Dtdb\BuilderBundle\Entity\Card
     */
    public function getOutfit()
    {
    	return $this->outfit;
    }

    /**
     * Set lastPack
     *
     * @param \Dtdb\BuilderBundle\Entity\Pack $lastPack
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
     * @return \Dtdb\BuilderBundle\Entity\Pack
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
    /**
     * @var \Dtdb\BuilderBundle\Entity\Deckchange
     */
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $changes;


    /**
     * Add changes
     *
     * @param \Dtdb\BuilderBundle\Entity\Deckchange $changes
     * @return Deck
     */
    public function addChange(\Dtdb\BuilderBundle\Entity\Deckchange $changes)
    {
        $this->changes[] = $changes;

        return $this;
    }

    /**
     * Remove changes
     *
     * @param \Dtdb\BuilderBundle\Entity\Deckchange $changes
     */
    public function removeChange(\Dtdb\BuilderBundle\Entity\Deckchange $changes)
    {
        $this->changes->removeElement($changes);
    }

    /**
     * Get changes
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getChanges()
    {
        return $this->changes;
    }
}
