<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @orm:Entity
 * @orm:Table(name="ping")
 * @orm:HasLifecycleCallbacks
 */
class Ping
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
     * @orm:ManyToOne(targetEntity="Whoot\WhootBundle\Entity\User")
     * @orm:JoinColumn(name="deleted_by", referencedColumnName="id")
     */
    protected $deletedBy;    

    /**
     * @orm:ManyToOne(targetEntity="Whoot\WhootBundle\Entity\User", inversedBy="pinged", cascade={"persist"})
     * @orm:JoinColumn(name="created_by", referencedColumnName="id")
     */
    protected $createdBy;

    /**
     * @orm:ManyToOne(targetEntity="Whoot\WhootBundle\Entity\User", inversedBy="pings", cascade={"persist"})
     * @orm:JoinColumn(name="user_id", referencedColumnName="id")
     */
    protected $user;

    public function __construct()
    {
        $this->status = 'Active';
        $this->createdBy = new ArrayCollection();
        $this->user = new ArrayCollection();
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
     * Set createdBy
     *
     * @param User $following
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