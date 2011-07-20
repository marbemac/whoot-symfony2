<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @ORM\Entity
 * @ORM\Table(name="invite")
 * @ORM\HasLifecycleCallbacks
 */
class Invite
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
     * @var integer $attending
     * @ORM\Column(type="integer")
     */
    protected $attending = 1;

    /**
     * @var string $description
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * @var string $venue
     * @ORM\Column(type="string", nullable=true)
     */
    protected $venue;

    /**
     * @var string $time
     * @ORM\Column(type="string", nullable=true)
     */
    protected $time;    

    /**
     * @var string $address
     * @ORM\Column(type="string", nullable=true)
     */
    protected $address;

    /**
     * @var float $lat
     * @ORM\Column(type="float", nullable=true)
     */
    protected $lat;

    /**
     * @var float $lon
     * @ORM\Column(type="float", nullable=true)
     */
    protected $lon;

    /**
     * @Assert\File(maxSize="6000000")
     */
    public $file;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $subdirectory = '';

    /**
     * @var string $path
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $path;

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
     * @ORM\ManyToOne(targetEntity="Whoot\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var User $deletedBy
     * @ORM\ManyToOne(targetEntity="Whoot\UserBundle\Entity\User")
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
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Location", inversedBy="invites")
     */
    protected $location;


    /**
     * @var Whoot\WhootBundle\Entity\Post
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Post", mappedBy="invite", cascade={"persist"})
     */
    protected $posts;

    /**
     * @var Whoot\WhootBundle\Entity\Comment
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Comment", mappedBy="post", cascade={"persist"})
     */
    protected $comments;


    /**
     * @var Whoot\VoteBundle\Entity\Vote
     *
     * @ORM\OneToMany(targetEntity="Whoot\VoteBundle\Entity\Vote", mappedBy="invite")
     */
    protected $votes;

    public function __construct() {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
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
     * Set venue
     *
     * @param string $venue
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
    }

    /**
     * Get venue
     *
     * @return string $venue
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * Set time
     *
     * @param string $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return string $time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param float $lat
     * @return void
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param float $lon
     * @return void
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
    }

    /**
     * @return float
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * @param integer $attending
     */
    public function setAttending($attending)
    {
        $this->attending = $attending;
    }

    /**
     * Get score
     *
     * @return integer $score
     */
    public function getAttending()
    {
        return $this->attending;
    }

    /**
     * @param integer $increment
     */
    public function incrementAttending($increment)
    {
        $this->attending += $increment;
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
     * Get a invites's location.
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

    public function getPosts()
    {
        return $this->posts;
    }

    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set a vote
     *
     * @param Whoot\VoteBundle\Entity\Vote $vote
     */
    public function setVote($vote)
    {
        $this->votes[] = $vote;
    }

    /**
     * Get votes
     *
     * @return Whoot\VoteBundle\Entity\Vote $votes
     */
    public function getVotes()
    {
        return $this->votes;
    }

    public function setSubdirectory($subdirectory)
    {
        $this->subdirectory = $subdirectory;
    }

    public function getSubdirectory()
    {
        return $this->subdirectory ? '/'.$this->subdirectory : '';
    }

    /**
     * @param string $path
     */
    public function setPath($path)
    {
        $this->path = $path;
    }

    /**
     * @return string $path
     */
    public function getPath()
    {
        return $this->path;
    }

    public function getFullPath()
    {
        return null === $this->path ? null : $this->getUploadRootDir().'/'.$this->path;
    }

    protected function getUploadRootDir()
    {
        return $_SERVER['DOCUMENT_ROOT'].'/uploads'.$this->getSubdirectory();
    }

    /**
     * @ORM\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
        if ($this->file) {
            // do whatever you want to generate a unique name
            $this->setPath(uniqid('i').'.'.$this->file->guessExtension());
        }
    }

    /**
     * @ORM\preUpdate
     */
    public function touchUpdated()
    {
        $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\postPersist
     */
    public function upload()
    {
        if (!isset($this->file)) {
            return;
        }

        // you must throw an exception here if the file cannot be moved
        // so that the entity is not persisted to the database
        // which the UploadedFile move() method does automatically
        $this->file->move($this->getUploadRootDir(), $this->path);

        unset($this->file);
    }

    /**
     * @ORM\postRemove
     */
    public function removeUpload()
    {
        if ($file = $this->getFullPath()) {
            unlink($file);
        }
    }
}