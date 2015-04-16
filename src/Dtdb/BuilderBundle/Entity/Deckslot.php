<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Deckslot
 */
class Deckslot
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
     * @var Dtdb\BuilderBundle\Entity\Deck
     */
    private $deck;

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
     * @param string $deck
     * @return Deck
     */
    public function setDeck($deck)
    {
    	$this->deck = $deck;
    
    	return $this;
    }
    
    /**
     * Get deck
     *
     * @return string
     */
    public function getDeck()
    {
    	return $this->deck;
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
     * @return \Dtdb\CardsBundle\Entity\Card
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
     * @return Deckslot
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
