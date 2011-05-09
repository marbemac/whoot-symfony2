<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @orm:Entity
 * @orm:Table(name="users_posts")
 * @orm:HasLifecycleCallbacks
 */
class UsersPosts
{
    /**
     * @var integer $id
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $status
     * @orm:Column(type="string")
     */
    protected $status;

    /**
     * @var string $approved
     * @orm:Column(type="boolean")
     */
    protected $approved;

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
     * @var User $deletedBy
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User")
     * @orm:JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    protected $deletedBy;    

    /**
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User", inversedBy="post", cascade={"persist", "remove"})
     * @orm:JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\Post", inversedBy="user", cascade={"persist", "remove"})
     * @orm:JoinColumn(name="post_id", referencedColumnName="id")
     */
    protected $post;

    public function __construct()
    {
        $this->status = 'Active';
        $this->users = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->approved = true;
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
     * Set approved
     *
     * @param bool $approved
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;
    }

    /**
     * Get approved
     *
     * @return bool $approved
     */
    public function getApproved()
    {
        return $this->approved;
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
     * Set post
     *
     * @param integer $post
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * Get post
     *
     * @return integer $post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set user
     *
     * @param integer $user
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Get user
     *
     * @return integer $user
     */
    public function getUser()
    {
        return $this->user;
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