<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dtdb\BuilderBundle\Entity\User;

/**
 * Review
 */
class Review
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
     * @var \DateTime
     */
    private $dateupdate;

    /**
     * @var string
     */
    private $rawtext;

    /**
     * @var string
     */
    private $text;

    /**
     * @var integer
     */
    private $nbvotes;

    /**
     * @var \Dtdb\BuilderBundle\Entity\Card
     */
    private $card;

    /**
     * @var \Dtdb\BuilderBundle\Entity\User
     */
    private $user;

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $votes;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set datecreation
     *
     * @param \DateTime $datecreation
     * @return Review
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
     * @return Review
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
     * Set rawtext
     *
     * @param string $rawtext
     * @return Review
     */
    public function setRawtext($rawtext)
    {
        $this->rawtext = $rawtext;

        return $this;
    }

    /**
     * Get rawtext
     *
     * @return string
     */
    public function getRawtext()
    {
        return $this->rawtext;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return Review
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set nbvotes
     *
     * @param integer $nbvotes
     * @return Review
     */
    public function setNbvotes($nbvotes)
    {
        $this->nbvotes = $nbvotes;

        return $this;
    }

    /**
     * Get nbvotes
     *
     * @return integer
     */
    public function getNbvotes()
    {
        return $this->nbvotes;
    }

    /**
     * Set card
     *
     * @param \Dtdb\BuilderBundle\Entity\Card $card
     * @return Review
     */
    public function setCard(\Dtdb\BuilderBundle\Entity\Card $card = null)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card
     *
     * @return \Dtdb\BuilderBundle\Entity\Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set user
     *
     * @param \Dtdb\BuilderBundle\Entity\User $user
     * @return Review
     */
    public function setUser(User $user = null)
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
     * Add votes
     *
     * @param \Dtdb\BuilderBundle\Entity\User $votes
     * @return Review
     */
    public function addVote(\Dtdb\BuilderBundle\Entity\User $user)
    {
        $this->votes[] = $user;

        return $this;
    }

    /**
     * Remove votes
     *
     * @param \Dtdb\BuilderBundle\Entity\User $votes
     */
    public function removeVote(\Dtdb\BuilderBundle\Entity\User $votes)
    {
        $this->votes->removeElement($votes);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVotes()
    {
        return $this->votes;
    }
}
