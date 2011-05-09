<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @orm:Entity
 * @orm:Table(name="user_following")
 * @orm:HasLifecycleCallbacks
 */
class UserFollowing
{
    /**
     * @var integer $id
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:generatedValue(strategy="AUTO")
     */
    protected $id;

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
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User", inversedBy="following", cascade={"persist"})
     * @orm:JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    /**
     * @orm:ManyToOne(targetEntity="Socialite\SocialiteBundle\Entity\User", inversedBy="followers", cascade={"persist"})
     * @orm:JoinColumn(name="following_id", referencedColumnName="id")
     */
    protected $following;

    public function __construct()
    {
        $this->status = 'Active';
        $this->users = new ArrayCollection();
        $this->posts = new ArrayCollection();
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
     * Set following
     *
     * @param integer $following
     */
    public function setFollowing($following)
    {
        $this->following = $following;
    }

    /**
     * Get following
     *
     * @return integer $following
     */
    public function getFollowing()
    {
        return $this->following;
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
}