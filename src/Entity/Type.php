<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="type",
 *     indexes={
 *         @ORM\Index(name="name_index", columns={"name"})
 *     }
 * )
 */
class Type
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
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    protected $name;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Card", mappedBy="type")
     */
    protected $cards;

    /**
     * @var Suit
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Suit", inversedBy="types")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="suit_id", referencedColumnName="id")
     * })
     */
    protected $suit;

    public function __construct()
    {
        $this->cards = new ArrayCollection();
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
     * @param Card $cards
     * @return Type
     */
    public function addCard(Card $cards)
    {
        $this->cards[] = $cards;

        return $this;
    }

    /**
     * Remove cards
     *
     * @param Card $cards
     */
    public function removeCard(Card $cards)
    {
        $this->cards->removeElement($cards);
    }

    /**
     * Get cards
     *
     * @return Collection
     */
    public function getCards()
    {
        return $this->cards;
    }

    /**
     * Set suit
     *
     * @param Suit|null $suit
     * @return Type
     */
    public function setSuit(Suit $suit = null)
    {
        $this->suit = $suit;

        return $this;
    }

    /**
     * Get suit
     *
     * @return Suit
     */
    public function getSuit()
    {
        return $this->suit;
    }
}
