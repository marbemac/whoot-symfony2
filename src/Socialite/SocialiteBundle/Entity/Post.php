<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @orm:Entity
 * @orm:Table(name="post")
 * @orm:HasLifecycleCallbacks
 */
class Post
{
    /**
     * @var integer $id
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:generatedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $type
     * @orm:Column(type="string", length="255")
     * 
     * @assert:NotBlank()
     * @assert:Choice(
     *     choices = {"working", "low_in", "low_out", "big_out"},
     *     message = "Choose a valid activity."
     * )
     */
    private $type;
    
    /**
     * @var string $status
     * @orm:Column(type="string")
     */
    protected $status;

    /**
     * @var string $note
     * @orm:Column(type="string")
     */
    protected $note;

    /**
     * @var dateTime $updatedAt
     * @orm:Column(type="datetime", name="updated_at", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var dateTime $createdAt
     * @orm:Column(type="datetime", name="created_at", nullable=true)
     */
    protected $createdAt;

    /**
     * @var dateTime $deletedAt
     * @orm:Column(type="datetime", name="deleted_at", nullable=true)
     */
    protected $deletedAt;

    /**
     * @var User $createdBy
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User")
     * @orm:JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @var User $deletedBy
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User")
     * @orm:JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    protected $deletedBy;

    /**
     * The allowable post types...
     * @var array $postTypes
     */
    private $postTypes = array('working', 'low_in', 'low_out', 'big_out');

    /**
     * @var Limelight\LimelightBundle\Entity\User
     *
     * @orm:OneToMany(targetEntity="Socialite\SocialiteBundle\Entity\UsersPosts", mappedBy="post", cascade={"persist"})
     */
    protected $users;

    public function __construct() {
        $this->users = new ArrayCollection();
        $this->status = 'Active';
        $this->note = '';
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

    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @orm:prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }

    /**
     * @orm:preUpdate
     */
    public function touchUpdated()
    {
        $this->updatedAt = new \DateTime();
    }
}