<?php

namespace App\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * @ORM\Entity()
 * @ORM\Table(name="user")
 */
class User extends BaseUser
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
     * @var int
     *
     * @ORM\Column(name="reputation", type="integer", nullable=true)
     */
    protected $reputation;

    /**
     * @var int
     *
     * @ORM\Column(name="gang", type="string", nullable=false)
     */
    protected $gang;

    /**
     * @var DateTime
     *
     * @ORM\Column(name="creation", type="datetime", nullable=true)
     */
    protected $creation;

    /**
     * @var string
     *
     * @ORM\Column(name="resume", type="text", nullable=true)
     */
    protected $resume;

    /**
     * @var int
     *
     * @ORM\Column(name="role", type="integer", nullable=true)
     */
    protected $role;

    /**
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true)
     */
    protected $status;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", length=255, nullable=true)
     */
    protected $avatar;

    /**
     * @var int
     *
     * @ORM\Column(name="donation", type="integer", nullable=false)
     */
    protected $donation;

    /**
     * @var bool
     *
     * @ORM\Column(name="notif_author", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $notif_author = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="notif_commenter", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $notif_commenter = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="notif_mention", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $notif_mention = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="notif_follow", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $notif_follow = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="notif_successor", type="boolean", nullable=false, options={"default"="1"})
     */
    protected $notif_successor = true;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deck", mappedBy="user", cascade={"remove"})
     * @ORM\OrderBy({
     *     "dateupdate"="DESC"
     * })
     */
    protected $decks;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="user")
     */
    protected $decklists;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="author")
     * @ORM\OrderBy({
     *     "creation"="DESC"
     * })
     */
    protected $comments;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Review", mappedBy="user")
     * @ORM\OrderBy({
     *     "datecreation"="DESC"
     * })
     */
    protected $reviews;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Decklist", mappedBy="favorites", cascade={"remove"})
     */
    protected $favorites;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Decklist", mappedBy="votes", cascade={"remove"})
     */
    protected $votes;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Review", mappedBy="votes", cascade={"remove"})
     */
    protected $reviewvotes;
    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="followers")
     */
    protected $following;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="following")
     * @ORM\JoinTable(name="follow",
     *   joinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="follower_id", referencedColumnName="id")
     *   }
     * )
     */
    protected $followers;

    public function __construct()
    {
        $this->decks = new ArrayCollection();
        $this->decklists = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->reputation = 1;
        $this->gang = 'neutral';
        $this->creation = new DateTime();
        $this->donation = 0;
        parent::__construct();
    }

    /**
     * Set reputation
     *
     * @param integer $reputation
     * @return User
     */
    public function setReputation($reputation)
    {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation
     *
     * @return integer
     */
    public function getReputation()
    {
        return $this->reputation;
    }

    /**
     * Set creation
     *
     * @param DateTime $creation
     * @return User
     */
    public function setCreation($creation)
    {
        $this->creation = $creation;

        return $this;
    }

    /**
     * Get creation
     *
     * @return DateTime
     */
    public function getCreation()
    {
        return $this->creation;
    }

    /**
     * Set resume
     *
     * @param string $resume
     * @return User
     */
    public function setResume($resume)
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume
     *
     * @return string
     */
    public function getResume()
    {
        return $this->resume;
    }

    /**
     * Set status
     *
     * @param integer $status
     * @return User
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set gang
     *
     * @param string $gang
     * @return User
     */
    public function setGang($gang)
    {
        $this->gang = $gang;

        return $this;
    }

    /**
     * Get gang
     *
     * @return string
     */
    public function getGang()
    {
        return $this->gang;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     * @return User
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set donation
     *
     * @param integer $donation
     * @return User
     */
    public function setDonation($donation)
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation
     *
     * @return integer
     */
    public function getDonation()
    {
        return $this->donation;
    }

    /**
     * Set deck
     *
     * @param string $decks
     * @return User
     */
    public function setDecks($decks)
    {
        $this->decks = $decks;

        return $this;
    }

    /**
     * Get deck
     *
     * @return Collection
     */
    public function getDecks()
    {
        return $this->decks;
    }

    /**
     * Set decklists
     *
     * @param string $decklists
     * @return User
     */
    public function setDecklists($decklists)
    {
        $this->decklists = $decklists;

        return $this;
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
     * Set comments
     *
     * @param string $comments
     * @return User
     */
    public function setComments($comments)
    {
        $this->comments = $comments;

        return $this;
    }

    /**
     * Get comments
     *
     * @return Collection
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add to favorites
     *
     * @param Decklist $decklist
     * @return User
     */
    public function addFavorite($decklist)
    {
        $decklist->addFavorite($this);
        $this->favorites[] = $decklist;

        return $this;
    }

    /**
     * Remove from favorites
     *
     * @param Decklist $decklist
     * @return User
     */
    public function removeFavorite($decklist)
    {
        $decklist->removeFavorite($this);
        $this->favorites->removeElement($decklist);

        return $this;
    }


    /**
     * Get favorites
     *
     * @return Collection
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Set votes
     *
     * @param Decklist $decklist
     * @return User
     */
    public function addVote($decklist)
    {
        $decklist->addVote($this);
        $this->votes[] = $decklist;

        return $this;
    }

    /**
     * Get votes
     *
     * @return Collection
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * Set following
     *
     * @param User $user
     * @return User
     */
    public function addFollowing($user)
    {
        $user->addFollower($this);
        $this->following[] = $user;

        return $this;
    }

    /**
     * Get following
     *
     * @return Collection
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * Add follower
     *
     * @param User $follower
     * @return User
     */
    public function addFollower($user)
    {
        $this->followers[] = $user;

        return $this;
    }

    /**
     * Get followers
     *
     * @return Collection
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    /**
     * @return float
     */
    public function getMaxNbDecks()
    {
        return 500 + floor($this->reputation / 10);
    }

    /**
     * Set notif_author
     *
     * @param boolean $notifAuthor
     * @return User
     */
    public function setNotifAuthor($notifAuthor)
    {
        $this->notif_author = $notifAuthor;

        return $this;
    }

    /**
     * Get notif_author
     *
     * @return boolean
     */
    public function getNotifAuthor()
    {
        return $this->notif_author;
    }

    /**
     * Set notif_commenter
     *
     * @param boolean $notifCommenter
     * @return User
     */
    public function setNotifCommenter($notifCommenter)
    {
        $this->notif_commenter = $notifCommenter;

        return $this;
    }

    /**
     * Get notif_commenter
     *
     * @return boolean
     */
    public function getNotifCommenter()
    {
        return $this->notif_commenter;
    }

    /**
     * Set notif_mention
     *
     * @param boolean $notifMention
     * @return User
     */
    public function setNotifMention($notifMention)
    {
        $this->notif_mention = $notifMention;

        return $this;
    }

    /**
     * Get notif_mention
     *
     * @return boolean
     */
    public function getNotifMention()
    {
        return $this->notif_mention;
    }

    /**
     * Set notif_follow
     *
     * @param boolean $notifFollow
     * @return User
     */
    public function setNotifFollow($notifFollow)
    {
        $this->notif_follow = $notifFollow;

        return $this;
    }

    /**
     * Get notif_follow
     *
     * @return boolean
     */
    public function getNotifFollow()
    {
        return $this->notif_follow;
    }

    /**
     * Set notif_successor
     *
     * @param boolean $notifSuccessor
     * @return User
     */
    public function setNotifSuccessor($notifSuccessor)
    {
        $this->notif_successor = $notifSuccessor;

        return $this;
    }

    /**
     * Get notif_successor
     *
     * @return boolean
     */
    public function getNotifSuccessor()
    {
        return $this->notif_successor;
    }

    /**
     * Add decks
     *
     * @param Deck $decks
     * @return User
     */
    public function addDeck(Deck $decks)
    {
        $this->decks[] = $decks;

        return $this;
    }

    /**
     * Remove decks
     *
     * @param Deck $decks
     */
    public function removeDeck(Deck $decks)
    {
        $this->decks->removeElement($decks);
    }

    /**
     * Add decklists
     *
     * @param Decklist $decklists
     * @return User
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
     * Add comments
     *
     * @param Comment $comments
     * @return User
     */
    public function addComment(Comment $comments)
    {
        $this->comments[] = $comments;

        return $this;
    }

    /**
     * Remove comments
     *
     * @param Comment $comments
     */
    public function removeComment(Comment $comments)
    {
        $this->comments->removeElement($comments);
    }

    /**
     * Remove votes
     *
     * @param Decklist $votes
     */
    public function removeVote(Decklist $votes)
    {
        $this->votes->removeElement($votes);
    }

    /**
     * Remove following
     *
     * @param User $following
     */
    public function removeFollowing(User $following)
    {
        $this->following->removeElement($following);
    }

    /**
     * Remove followers
     *
     * @param User $followers
     */
    public function removeFollower(User $followers)
    {
        $this->followers->removeElement($followers);
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
     * Set role
     *
     * @param integer $role
     * @return User
     */
    public function setRole($role)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return integer
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Add reviewvotes
     *
     * @param Review $review
     * @return User
     */
    public function addReviewvote(Review $review)
    {
        $review->addVote($this);
        $this->reviewvotes[] = $review;

        return $this;
    }

    /**
     * Remove reviewvotes
     *
     * @param Review $reviewvotes
     */
    public function removeReviewvote(Review $reviewvotes)
    {
        $this->reviewvotes->removeElement($reviewvotes);
    }

    /**
     * Get reviewvotes
     *
     * @return Collection
     */
    public function getReviewvotes()
    {
        return $this->reviewvotes;
    }

    /**
     * Add reviews
     *
     * @param Review $reviews
     * @return User
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
