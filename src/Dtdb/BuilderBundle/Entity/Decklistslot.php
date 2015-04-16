<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Decklistslot
 */
class Decklistslot
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var integer
     */
    private $quantity;

    /**
     * @var Dtdb\BuilderBundle\Entity\Decklist
     */
    private $decklist;

    /**
     * @var Dtdb\CardsBundle\Entity\Card
     */
    private $card;
    
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
     * Set quantity
     *
     * @param integer $quantity
     * @return Deckcontent
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;
    
        return $this;
    }

    /**
     * Get quantity
     *
     * @return integer 
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set deck
     *
     * @param string $decklist
     * @return Deck
     */
    public function setDecklist($decklist)
    {
    	$this->decklist = $decklist;
    
    	return $this;
    }
    
    /**
     * Get decklist
     *
     * @return string
     */
    public function getDecklist()
    {
    	return $this->decklist;
    }

    /**
     * Set card
     *
     * @param string $card
     * @return Card
     */
    public function setCard($card)
    {
    	$this->card = $card;
    
    	return $this;
    }
    
    /**
     * Get card
     *
     * @return string
     */
    public function getCard()
    {
    	return $this->card;
    }
    
    /**
     * @var integer
     */
    private $start;


    /**
     * Set start
     *
     * @param integer $start
     * @return Decklistslot
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Get start
     *
     * @return integer 
     */
    public function getStart()
    {
        return $this->start;
    }
}
