<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Deckchange
 */
class Deckchange
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $datecreation;

    /**
     * @var string
     */
    private $variation;

    /**
     * @var \Dtdb\BuilderBundle\Entity\Deck
     */
    private $deck;


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
     * Set datecreation
     *
     * @param \DateTime $datecreation
     * @return Change
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
     * Set variation
     *
     * @param string $variation
     * @return Change
     */
    public function setVariation($variation)
    {
        $this->variation = $variation;

        return $this;
    }

    /**
     * Get variation
     *
     * @return string
     */
    public function getVariation()
    {
        return $this->variation;
    }

    /**
     * Set deck
     *
     * @param \Dtdb\BuilderBundle\Entity\Deck $deck
     * @return Change
     */
    public function setDeck(\Dtdb\BuilderBundle\Entity\Deck $deck = null)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \Dtdb\BuilderBundle\Entity\Deck
     */
    public function getDeck()
    {
        return $this->deck;
    }
    /**
     * @var boolean
     */
    private $saved;


    /**
     * Set saved
     *
     * @param boolean $saved
     * @return Deckchange
     */
    public function setSaved($saved)
    {
        $this->saved = $saved;

        return $this;
    }

    /**
     * Get saved
     *
     * @return boolean
     */
    public function getSaved()
    {
        return $this->saved;
    }
}
