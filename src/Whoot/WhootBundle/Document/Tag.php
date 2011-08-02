<?php

namespace Whoot\WhootBundle\Document;

use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;

/**
 * @MongoDB\Document
 */
class Tag implements ObjectInterface
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

    /**
     * @MongoDB\Field(type="int")
     */
    protected $score;
    
    /**
     * @MongoDB\Field(type="boolean")
     */
    protected $isTrendable;

    /**
     * @MongoDB\Field(type="boolean")
     */
    protected $isStopWord;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $createdAt;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $createdBy;

    public function __construct() {
        $this->isStopWord = false;
        $this->isTrendable = false;
        $this->score = 0;
    }

    /**
     * @return integer $id
     */
    public function getId()
    {
        return new \MongoId($this->id);
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
        $this->setSlug($name);
    }

    /**
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return string $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Given string, compute and set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = new SlugNormalizer($slug);
    }

    /**
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param integer $score
     */
    public function setScore($score)
    {
        $this->score = $score;
    }

    /**
     * @return integer $score
     */
    public function getScore()
    {
        return $this->score;
    }

    public function getIsTrendable()
    {
        return $this->isTrendable;
    }

    public function setIsTrendable($isTrendable)
    {
        $this->isTrendable = $isTrendable;
    }

    public function getIsStopWord()
    {
        return $this->isStopWord;
    }

    public function setIsStopWord($isStopWord)
    {
        $this->isStopWord = $isStopWord;
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

    /**
     * @MongoDB\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }
}