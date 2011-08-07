<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;

/**
 * @MongoDB\Document
 */
class LList implements ObjectInterface
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;
    
    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $slug;

    /** @MongoDB\Field(type="int") */
    protected $userCount;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $updatedAt;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $createdAt;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $deletedAt;

    /**
     * @MongoDB\Field(type="object_id")
     * @MongoDB\Index(order="asc")
     */
    protected $createdBy;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $deletedBy;

    /**
     * @MongoDB\field(type="hash")
     */
    protected $users;

    public function __construct() {
        $this->status = 'Active';
        $this->userCount = 0;
    }

    /**
     * @return MongoId $id
     */
    public function getId()
    {
        return new \MongoId($this->id);
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->slug = new SlugNormalizer($name);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getUserCount()
    {
        return $this->userCount;
    }

    public function getUsers()
    {
        return $this->users;
    }

    public function findUser($userId)
    {
        return isset($this->users[$userId]) ? $this->users[$userId] : false;
    }

    public function setCreatedBy($createdBy, $embed = false)
    {
        if (is_object($createdBy) && !$embed)
        {
            $this->createdBy = $createdBy->getId();
        }
        else
        {
            $this->createdBy = $createdBy;
        }
    }

    /**
     * @return ObjectId $createdBy
     */
    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setDeletedBy($deletedBy)
    {
        if (is_object($deletedBy))
        {
            $this->createdBy = $deletedBy->getId();
        }
        else
        {
            $this->createdBy = $deletedBy;
        }
    }

    /**
     * @return User $deletedBy
     */
    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    /**
     * @return datetime $createdAt
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    /**
     * @return datetime $updatedAt
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    /**
     * @param datetime $deletedAt
     */
    public function setDeletedAt(\DateTime $deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    /**
     * @return datetime $deletedAt
     */
    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    /**
     * @MongoDB\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }

    /**
     * @MongoDB\preUpdate
     */
    public function touchUpdated()
    {
        $this->updatedAt = new \DateTime();
    }
}