<?php

namespace Dtdb\BuilderBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Dtdb\BuilderBundle\Entity\User;
use Dtdb\BuilderBundle\Entity\Decklist;

/**
 * Comment
 */
class Comment
{
    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $text;

    /**
     * @var \DateTime
     */
    private $creation;

    /**
     * @var User
     */
    private $author;

    /**
     * @var Decklist
     */
    private $decklist;

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
     * Set text
     *
     * @param string $text
     * @return Comment
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
     * Set creation
     *
     * @param \DateTime $creation
     * @return Comment
     */
    public function setCreation($creation)
    {
        $this->creation = $creation;
    
        return $this;
    }

    /**
     * Get creation
     *
     * @return \DateTime 
     */
    public function getCreation()
    {
        return $this->creation;
    }

    /**
     * Set author
     *
     * @param string $author
     * @return User
     */
    public function setAuthor($author)
    {
    	$this->author = $author;
    
    	return $this;
    }
    
    /**
     * Get author
     *
     * @return string
     */
    public function getAuthor()
    {
    	return $this->author;
    }

    /**
     * Set decklist
     *
     * @param string $decklist
     * @return Decklist
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
}
