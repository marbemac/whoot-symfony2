<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Whoot\WhootBundle\Util\SlugNormalizer;

/**
 * @MongoDB\EmbeddedDocument
 */
class CurrentLocation
{
    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $type;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $state;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $city;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $school;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $timezone;

    public function __construct($locationData)
    {
        // Set the location IDs
        $keys = array('state', 'city', 'school');
        foreach ($locationData as $key => $id)
        {
            $this->$keys[$key] = $id;
        }
    }

    // Gets the most specific ID on thie current location.
    public function getId()
    {
        $keys = array('school', 'city', 'state');
        foreach ($keys as $check)
        {
            if ($this->$check)
            {
                return $this->$check;
            }
        }
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setState($stateId)
    {
        $this->state = $stateId;
    }

    public function getState()
    {
        return new \MongoId($this->state);
    }

    public function setCity($cityId)
    {
        $this->city = $cityId;
    }

    public function getCity()
    {
        return new \MongoId($this->city);
    }

    public function setSchool($schoolId)
    {
        $this->school = $schoolId;
    }

    public function getSchool()
    {
        return new \MongoId($this->school);
    }

    public function setTimezone($timezone)
    {
        $this->timezone = $timezone;
    }

    public function getTimezone()
    {
        return $this->timezone;
    }
}