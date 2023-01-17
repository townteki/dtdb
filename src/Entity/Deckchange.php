<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="deckchange",
 *     indexes={
 *         @ORM\Index(name="deck_saved_index", columns={"deck_id", "saved"})
 *     }
 * )
 */
class Deckchange
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
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="datecreation", type="datetime", nullable=false)
     */
    protected $datecreation;

    /**
     * @var string
     *
     * @ORM\Column(name="variation", type="string", length=1024, nullable=false)
     */
    protected $variation;

    /**
     * @var bool
     *
     * @ORM\Column(name="saved", type="boolean", nullable=false)
     */
    protected $saved;

    /**
     * @var Deck
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="changes")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="deck_id", referencedColumnName="id")
     * })
     */
    protected $deck;

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
     * @param DateTime $datecreation
     * @return Deckchange
     */
    public function setDatecreation($datecreation)
    {
        $this->datecreation = $datecreation;

        return $this;
    }

    /**
     * Get datecreation
     *
     * @return DateTime
     */
    public function getDatecreation()
    {
        return $this->datecreation;
    }

    /**
     * Set variation
     *
     * @param string $variation
     * @return Deckchange
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
     * @param Deck|null $deck
     * @return Deckchange
     */
    public function setDeck(Deck $deck = null)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return Deck
     */
    public function getDeck()
    {
        return $this->deck;
    }

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
