<?php

namespace Whoot\WhootBundle\Document;

use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;

/**
 * @MongoDB\Document
 */
class Location implements ObjectInterface
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
    protected $stateCode;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $stateName;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $cityName;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $schoolName;

    public function __construct() {
        $this->status = 'Active';
    }

    public function __toString()
    {
        return $this->cityName.', '.$this->getStateCode();
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

    /**
     * @param string $cityName
     */
    public function setCityName($cityName)
    {
        $this->cityName = $cityName;
    }

    /**
     * @return string
     */
    public function getCityName()
    {
        return $this->cityName;
    }

    /**
     * @param string $stateCode
     */
    public function setStateCode($stateCode)
    {
        $this->stateCode = $stateCode;
    }

    /**
     * @return string
     */
    public function getStateCode()
    {
        return $this->stateCode;
    }

    /**
     * @param string $stateName
     */
    public function setStateName($stateName)
    {
        $this->stateName = $stateName;
    }

    /**
     * @return string
     */
    public function getStateName()
    {
        return $this->stateName;
    }

    /**
     * @param string $schoolName
     */
    public function setSchoolName($schoolName)
    {
        $this->schoolName = $schoolName;
    }

    /**
     * @return string
     */
    public function getSchoolName()
    {
        return $this->schoolName;
    }
}