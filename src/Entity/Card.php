<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="card",
 *     indexes={
 *          @ORM\Index(name="code_index", columns={"code"})
 *     }
 * )
 */
class Card
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
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="ts", type="datetime", nullable=false)
     */
    protected $ts;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=5, nullable=false)
     */
    protected $code;

    /**
     * @var int
     *
     * @ORM\Column(name="number", type="smallint", nullable=false)
     */
    protected $number;

    /**
     * @var int
     *
     * @ORM\Column(name="quantity", type="smallint", nullable=false)
     */
    protected $quantity;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255, nullable=false)
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="keywords", type="string", length=255, nullable=true)
     */
    protected $keywords;

    /**
     * @var string
     *
     * @ORM\Column(name="text", type="string", length=1024, nullable=true)
     */
    protected $text;

    /**
     * @var string
     *
     * @ORM\Column(name="flavor", type="string", length=1024, nullable=true)
     */
    protected $flavor;

    /**
     * @var string
     *
     * @ORM\Column(name="illustrator", type="string", length=255, nullable=true)
     */
    protected $illustrator;

    /**
     * @var int
     *
     * @ORM\Column(name="cost", type="smallint", nullable=true)
     */
    protected $cost;

    /**
     * @var int
     *
     * @ORM\Column(name="handrank", type="smallint", nullable=true)
     */
    protected $rank;

    /**
     * @var int
     *
     * @ORM\Column(name="upkeep", type="smallint", nullable=true)
     */
    protected $upkeep;

    /**
     * @var int
     *
     * @ORM\Column(name="production", type="smallint", nullable=true)
     */
    protected $production;

    /**
     * @var int
     *
     * @ORM\Column(name="bullets", type="smallint", nullable=true)
     */
    protected $bullets;

    /**
     * @var int
     *
     * @ORM\Column(name="influence", type="smallint", nullable=true)
     */
    protected $influence;

    /**
     * @var int
     *
     * @ORM\Column(name="control", type="smallint", nullable=true)
     */
    protected $control;

    /**
     * @var int
     *
     * @ORM\Column(name="wealth", type="smallint", nullable=true)
     */
    protected $wealth;

    /**
     * @var string
     *
     * @ORM\Column(name="octgnid", type="string", length=255, nullable=true)
     */
    protected $octgnid;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_multiple", type="boolean", nullable=false)
     */
    protected $isMultiple = false;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="outfit")
     */
    protected $decklists;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Review", mappedBy="card")
     * @ORM\OrderBy({
     *     "datecreation"="DESC"
     * })
     */
    protected $reviews;

    /**
     * @var Pack
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Pack", inversedBy="cards")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pack_id", referencedColumnName="id")
     * })
     */
    protected $pack;

    /**
     * @var Type
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Type", inversedBy="cards")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="type_id", referencedColumnName="id")
     * })
     */
    protected $type;

    /**
     * @var Shooter
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Shooter", inversedBy="cards")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="shooter_id", referencedColumnName="id")
     * })
     */
    protected $shooter;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Gang", mappedBy="cards")
     */
    protected $gangs;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->decklists = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->gangs = new ArrayCollection();
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
     * Set ts
     *
     * @param DateTime $ts
     * @return Card
     */
    public function setTs($ts)
    {
        $this->ts = $ts;

        return $this;
    }

    /**
     * Get ts
     *
     * @return DateTime
     */
    public function getTs()
    {
        return $this->ts;
    }

    /**
     * Set code
     *
     * @param string $code
     * @return Card
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set number
     *
     * @param integer $number
     * @return Card
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number
     *
     * @return integer
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set quantity
     *
     * @param integer $quantity
     * @return Card
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
     * Set title
     *
     * @param string $title
     * @return Card
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set keywords
     *
     * @param string $keywords
     * @return Card
     */
    public function setKeywords($keywords)
    {
        $this->keywords = $keywords;

        return $this;
    }

    /**
     * Get keywords
     *
     * @return string
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Set text
     *
     * @param string $text
     * @return Card
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
     * Set flavor
     *
     * @param string $flavor
     * @return Card
     */
    public function setFlavor($flavor)
    {
        $this->flavor = $flavor;

        return $this;
    }

    /**
     * Get flavor
     *
     * @return string
     */
    public function getFlavor()
    {
        return $this->flavor;
    }

    /**
     * Set illustrator
     *
     * @param string $illustrator
     * @return Card
     */
    public function setIllustrator($illustrator)
    {
        $this->illustrator = $illustrator;

        return $this;
    }

    /**
     * Get illustrator
     *
     * @return string
     */
    public function getIllustrator()
    {
        return $this->illustrator;
    }

    /**
     * Set cost
     *
     * @param integer $cost
     * @return Card
     */
    public function setCost($cost)
    {
        $this->cost = $cost;

        return $this;
    }

    /**
     * Get cost
     *
     * @return integer
     */
    public function getCost()
    {
        return $this->cost;
    }

    /**
     * Set rank
     *
     * @param integer $rank
     * @return Card
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return integer
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * Set upkeep
     *
     * @param integer $upkeep
     * @return Card
     */
    public function setUpkeep($upkeep)
    {
        $this->upkeep = $upkeep;

        return $this;
    }

    /**
     * Get upkeep
     *
     * @return integer
     */
    public function getUpkeep()
    {
        return $this->upkeep;
    }

    /**
     * Set production
     *
     * @param integer $production
     * @return Card
     */
    public function setProduction($production)
    {
        $this->production = $production;

        return $this;
    }

    /**
     * Get production
     *
     * @return integer
     */
    public function getProduction()
    {
        return $this->production;
    }

    /**
     * Set bullets
     *
     * @param integer $bullets
     * @return Card
     */
    public function setBullets($bullets)
    {
        $this->bullets = $bullets;

        return $this;
    }

    /**
     * Get bullets
     *
     * @return integer
     */
    public function getBullets()
    {
        return $this->bullets;
    }

    /**
     * Set influence
     *
     * @param integer $influence
     * @return Card
     */
    public function setInfluence($influence)
    {
        $this->influence = $influence;

        return $this;
    }

    /**
     * Get influence
     *
     * @return integer
     */
    public function getInfluence()
    {
        return $this->influence;
    }

    /**
     * Set control
     *
     * @param integer $control
     * @return Card
     */
    public function setControl($control)
    {
        $this->control = $control;

        return $this;
    }

    /**
     * Get control
     *
     * @return integer
     */
    public function getControl()
    {
        return $this->control;
    }

    /**
     * Add decklists
     *
     * @param Decklist $decklists
     * @return Card
     */
    public function addDecklist(Decklist $decklists)
    {
        $this->decklists[] = $decklists;

        return $this;
    }

    /**
     * Remove decklists
     *
     * @param Decklist $decklists
     */
    public function removeDecklist(Decklist $decklists)
    {
        $this->decklists->removeElement($decklists);
    }

    /**
     * Get decklists
     *
     * @return Collection
     */
    public function getDecklists()
    {
        return $this->decklists;
    }

    /**
     * Set pack
     *
     * @param Pack|null $pack
     * @return Card
     */
    public function setPack(Pack $pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return Pack
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * Set type
     *
     * @param Type|null $type
     * @return Card
     */
    public function setType(Type $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set shooter
     *
     * @param Shooter|null $shooter
     * @return Card
     */
    public function setShooter(Shooter $shooter = null)
    {
        $this->shooter = $shooter;

        return $this;
    }

    /**
     * Get shooter
     *
     * @return Shooter
     */
    public function getShooter()
    {
        return $this->shooter;
    }

    /**
     * Set gang
     *
     * @param Gang
     * @return Card
     */
    public function addGang(Gang $gang)
    {
        $this->gangs[] = $gang;

        return $this;
    }

    /**
     * Get gangs
     *
     * @return Collection
     */
    public function getGangs()
    {
        return $this->gangs;
    }

    /**
     * @return bool
     */
    public function hasGangAffiliations()
    {
        return !$this->gangs->isEmpty();
    }

    /**
     * @return array
     */
    public function getGangCodes()
    {
        return $this->gangs->map(function (Gang $gang) {
            return $gang->getCode();
        })->toArray();
    }

    /**
     * @return array
     */
    public function getGangNames()
    {
        return $this->gangs->map(function (Gang $gang) {
            return $gang->getName();
        })->toArray();
    }

    /**
     * @return array
     */
    public function getGangLetters()
    {
        return $this->gangs->map(function (Gang $gang) {
            return substr($gang->getCode(), 0, 1);
        })->toArray();
    }

    /**
     * Set wealth
     *
     * @param integer $wealth
     * @return Card
     */
    public function setWealth($wealth)
    {
        $this->wealth = $wealth;

        return $this;
    }

    /**
     * Get wealth
     *
     * @return integer
     */
    public function getWealth()
    {
        return $this->wealth;
    }

    /**
     * Set octgnid
     *
     * @param string $octgnid
     * @return Card
     */
    public function setOctgnid($octgnid)
    {
        $this->octgnid = $octgnid;

        return $this;
    }

    /**
     * Get octgnid
     *
     * @return string
     */
    public function getOctgnid()
    {
        return $this->octgnid;
    }

    /**
     * @return bool
     */
    public function getIsMultiple(): bool
    {
        return $this->isMultiple;
    }

    /**
     * @param bool $isMultiple
     * @return Card
     */
    public function setIsMultiple(bool $isMultiple): Card
    {
        $this->isMultiple = $isMultiple;
        return $this;
    }

    /**
     * Add reviews
     *
     * @param Review $reviews
     * @return Card
     */
    public function addReview(Review $reviews)
    {
        $this->reviews[] = $reviews;

        return $this;
    }

    /**
     * Remove reviews
     *
     * @param Review $reviews
     */
    public function removeReview(Review $reviews)
    {
        $this->reviews->removeElement($reviews);
    }

    /**
     * Get reviews
     *
     * @return Collection
     */
    public function getReviews()
    {
        return $this->reviews;
    }
}
