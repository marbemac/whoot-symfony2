<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @ORM\Entity
 * @ORM\Table(name="post")
 * @ORM\HasLifecycleCallbacks
 */
class Post
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $type
     * @ORM\Column(type="string", length="255")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\Choice(
     *     choices = {"working", "low_in", "low_out", "big_out"},
     *     message = "Choose a valid activity."
     * )
     */
    private $type;
    
    /**
     * @var string $status
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @var integer $score
     * @ORM\Column(type="integer")
     */
    protected $score = 0;
    
    /**
     * @var dateTime $updatedAt
     * @ORM\Column(type="datetime", name="updated_at", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var dateTime $createdAt
     * @ORM\Column(type="datetime", name="created_at", nullable=true)
     */
    protected $createdAt;

    /**
     * @var dateTime $deletedAt
     * @ORM\Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt;

    /**
     * @var User $createdBy
     * @ORM\ManyToOne(targetEntity="Whoot\WhootUserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var User $deletedBy
     * @ORM\ManyToOne(targetEntity="Whoot\WhootUserBundle\Entity\User")
     * @ORM\JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    protected $deletedBy;

    /**
     * The allowable post types...
     * @var array $postTypes
     */
    private $postTypes = array('working', 'low_in', 'low_out', 'big_out');

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Location", inversedBy="posts")
     */
    protected $location;

    /**
     * @var Whoot\WhootBundle\Entity\Invite
     *
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Invite", inversedBy="posts")
     */
    protected $invite;

    /**
     * @var Whoot\WhootBundle\Entity\Word
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\PostsWords", mappedBy="post")
     */
    protected $words;

    /**
     * @var Limelight\LimelightBundle\Entity\User
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Comment", mappedBy="post", cascade={"persist"})
     */
    protected $comments;

    public function __construct() {
        $this->comments = new ArrayCollection();
        $this->words = new ArrayCollection();
        $this->status = 'Active';
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        if (!in_array($type, $this->postTypes))
        {
            throw new HttpException('Invalid post type...');
        }
        else
        {
            $this->type = $type;
        }
    }

    /**
     * Get type
     *
     * @return string $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set score
     *
     * @param integer $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * Get score
     *
     * @return integer $score
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set word
     *
     * @param Word $word
     */
    public function setWord($word)
    {
        $this->words[] = $word;
    }

    /**
     * Set words
     *
     * @param array $words
     */
    public function setWords($words)
    {
        $this->words = $words;
    }

    /**
     * Get words
     *
     * @return string $words
     */
    public function getWords()
    {
        return $this->words;
    }

    /**
     * Set createdBy
     *
     * @param User $createdBy
     */
    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    /**
     * Get createdBy
     *
     * @return User $createdBy
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    /**
     * Set deletedBy
     *
     * @param User $deletedBy
     */
    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }

    /**
     * Get deletedBy
     *
     * @return User $deletedBy
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * Get createdAt
     *
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * Get updatedAt
     *
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    /**
     * Set deletedAt
     *
     * @param datetime $deletedAt
     */
    public function setDeletedAt(\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * Get deletedAt
     *
     * @return datetime $deletedAt
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * Get a post's location.
     *
     * @return Location $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     *
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getComments()
    {
        return $this->comments;
    }

    public function setInvite(Invite $invite)
    {
        $this->invite = $invite;
    }

    public function getInvite()
    {
        return $this->invite;
    }

    /**
     * @ORM\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\preUpdate
     */
    public function touchUpdated()
    {
        $this->updatedAt = new \DateTime();
    }
}