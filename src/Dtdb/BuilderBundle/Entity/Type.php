<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Type
 */
class Type
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
     * @var \Doctrine\Common\Collections\Collection
     */
    private $cards;

    /**
     * @var \Dtdb\BuilderBundle\Entity\Suit
     */
    private $suit;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Type
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
     * Add cards
     *
     * @param \Dtdb\BuilderBundle\Entity\Card $cards
     * @return Type
     */
    public function addCard(\Dtdb\BuilderBundle\Entity\Card $cards)
    {
        $this->cards[] = $cards;

        return $this;
    }

    /**
     * Remove cards
     *
     * @param \Dtdb\BuilderBundle\Entity\Card $cards
     */
    public function removeCard(\Dtdb\BuilderBundle\Entity\Card $cards)
    {
        $this->cards->removeElement($cards);
    }

    /**
     * Get cards
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Set suit
     *
     * @param \Dtdb\BuilderBundle\Entity\Suit $suit
     * @return Type
     */
    public function setSuit(\Dtdb\BuilderBundle\Entity\Suit $suit = null)
    {
        $this->suit = $suit;

        return $this;
    }

    /**
     * Get suit
     *
     * @return \Dtdb\BuilderBundle\Entity\Suit 
     */
    public function getSuit()
    {
        return $this->suit;
    }
}
