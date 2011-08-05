<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Whoot\WhootBundle\Model\ObjectInterface;

/**
 * @MongoDB\Document
 */
class Ping implements ObjectInterface
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index(order="asc")
     */
    protected $dateGroup;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $pingCount;

    /**
     * @MongoDB\ObjectId
     * @MongoDB\Index(order="asc")
     */
    protected $pinged;

    /**
     * @MongoDB\Field(type="hash")
     */
    protected $pingers;

    public function __construct() {
        $this->pingCount = 0;
        $this->pingers = array();
    }

    /**
     * @return integer $id
     */
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

    public function setDateGroup($date)
    {
        $this->dateGroup = $date;
    }

    public function getDateGroup()
    {
        return $this->dateGroup;
    }

    /**
     * @param string $userId
     */
    public function setPinged($userId)
    {
        $this->pinged = $userId;
    }

    /**
     * @return ObjectId $pinged
     */
    public function getPinged()
    {
        return $this->pinged;
    }

    public function addPinger($userId)
    {
        if (!in_array($userId, $this->pingers, true)) {
            $this->pingers[] = $userId;
            $this->pingCount++;
        }
    }

    public function getPingers()
    {
        $pingers = array();
        foreach ($this->pingers as $id)
        {
            $pingers[] = new \MongoId($id);
        }
        return $pingers;
    }

    public function removePinger($userId)
    {
        $key = array_search($userId, $this->pingers);
        if ($key)
        {
            unset($this->pingers[$key]);
            $this->pingCount--;
        }
    }
}