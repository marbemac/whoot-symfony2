<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @MongoDB\Document
 */
class Post implements ObjectInterface
{
    /**
     * The allowable post types...
     * @var array $postTypes
     */
    private $postTypes = array('working', 'low_in', 'low_out', 'big_out');

    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;
    
    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $score;

    /**
     * @MongoDB\Field(type="boolean")
     */
    protected $isCurrentPost;

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
    protected $location;

    /**
     * @MongoDB\EmbedMany(targetDocument="PostTag")
     */
    protected $tags;


    public function __construct() {
        $this->status = 'Active';
        $this->score = 0;
        $this->isCurrentPost = true;
        $this->tags = new ArrayCollection();
    }

    /**
     * @return MongoId $id
     */
    public function getId()
    {
        return new \MongoId($this->id);
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

    public function setIsCurrentPost($isCurrentPost)
    {
        $this->isCurrentPost = $isCurrentPost;
    }

    public function getIsCurrentPost()
    {
        return $this->isCurrentPost;
    }

    /**
     * @param PostTag $postTag
     */
    public function addTag(Tag $tag)
    {
        $postTag = new PostTag();
        $postTag->setTag($tag);
        $this->tags[] = $postTag;
    }

    /**
     * @param array $postTags
     */
    public function setTags($postTags)
    {
        $this->tags = $postTags;
    }

    /**
     * @return string $postTags
     */
    public function getTags()
    {
        return $this->tags;
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

/**
 * @MongoDB\EmbeddedDocument
 */
class PostTag
{
    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $tag;

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setTag(Tag $tag)
    {
        $this->tag = $tag->getId();
        $this->name = $tag->getName();
    }

    public function getTag()
    {
        return new \MongoId($this->tag);
    }
}