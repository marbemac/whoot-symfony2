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
     * @var string $note
     * @ORM\Column(type="string", nullable=true)
     */
    protected $note;

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
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Location", inversedBy="invites")
     */
    protected $location;


    /**
     * @var Limelight\LimelightBundle\Entity\User
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Post", mappedBy="invite", cascade={"persist"})
     */
    protected $posts;

    /**
     * @var Limelight\LimelightBundle\Entity\User
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Comment", mappedBy="post", cascade={"persist"})
     */
    protected $comments;

    public function __construct() {
        $this->posts = new ArrayCollection();
        $this->comments = new ArrayCollection();
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
     * Set note
     *
     * @param string $note
     */
    public function setNote($note)
    {
        $this->note = $note;
    }

    /**
     * Get note
     *
     * @return string $note
     */
    public function getNote()
    {
        return $this->note;
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