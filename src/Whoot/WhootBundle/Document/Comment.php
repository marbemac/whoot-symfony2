<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;

/**
 * @MongoDB\Document
 */
class Comment implements ObjectInterface
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @MongoDB\Field(type="string")
     * @Assert\NotBlank()
     * @Assert\MinLength(2)
     * @Assert\MaxLength(200)
     */
    protected $content;

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
     */
    protected $createdBy;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $deletedBy;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $post;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $invite;

    public function __construct() {
        $this->status = 'Active';
    }

    public function getId()
    {
        return new \MongoId($this->id);
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setContent($content)
    {
        $this->content = $content;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setDeletedAt($deletedAt)
    {
        $this->deletedAt = $deletedAt;
    }

    public function getDeletedAt()
    {
        return $this->deletedAt;
    }

    public function setCreatedBy($createdBy)
    {
        $this->createdBy = $createdBy;
    }

    public function getCreatedBy()
    {
        return $this->createdBy;
    }

    public function setDeletedBy($deletedBy)
    {
        $this->deletedBy = $deletedBy;
    }

    public function getDeletedBy()
    {
        return $this->deletedBy;
    }

    public function setPost($post)
    {
        $this->post = $post;
    }

    public function getPost()
    {
        return $this->post ? new \MongoId($this->post) : $this->post;
    }

    public function setInvite($invite)
    {
        $this->invite = $invite;
    }

    public function getInvite()
    {
        return $this->invite ? new \MongoId($this->invite) : $this->invite;
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