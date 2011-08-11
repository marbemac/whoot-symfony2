<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;
use Whoot\WhootBundle\Util\ArraySorter;

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
    protected $state;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $stateName;

    /**
     * @MongoDB\EmbedMany(targetDocument="City")
     */
    protected $cities;

    public function __construct() {
        $this->status = 'Active';
        $this->cities = array();
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
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;
        $this->stateName = $this->states[$this->state];
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getStateName()
    {
        return $this->stateName;
    }

    public function setCities($cities)
    {
        $this->cities = $cities;
    }

    public function getCities()
    {
        return $this->cities;
    }

    public function addCity($name)
    {
        $city = $this->getCity($name);
        if (!$this->cities || !$city)
        {
            $city = new City();
            $city->setState($this->state);
            $city->setName($name);
            $this->cities[] = $city;
            $sorter = new ArraySorter();
            $this->cities = $sorter->sortBySubkey($this->cities, 'slug', SORT_ASC);
        }
        else
        {
            $city->setStatus('Active');
        }
    }

    public function removeCity($cityId)
    {
        foreach ($this->cities as $key => $city)
        {
            if ($city->getId() == $cityId)
            {
                unset($this->cities[$key]);
            }
        }
    }

    public function getCity($name)
    {
        $slug = new SlugNormalizer($name);
        $slug .= '-'.strtolower($this->state);
        foreach ($this->cities as $city)
        {
            if ($city->getSlug() == $slug)
            {
                return $city;
            }
        }

        return false;
    }

    public function addSchool($cityId, $school)
    {
        foreach ($this->cities as $city)
        {
            if ($city->getId() == $cityId)
            {
                $city->addSchool($school);
            }
        }
    }

    public function getStates()
    {
        return $this->states;
    }

    // Find the embedded school and update the status
    public function setSchoolStatus($schoolId, $status)
    {
        foreach ($this->cities as $city)
        {
            foreach ($city->getSchools() as $school)
            {
                if ($school->getId() == $schoolId)
                {
                    $school->setStatus($status);
                    return true;
                }
            }
        }
 
        return false;
    }

    // Given a locationId and a type, build the full name.
    // For example, a id 124 and type school would build a name {schoolName} - {city}, {state}
    public function buildName($locationId, $type)
    {
        if ($type == 'State')
        {
            return $this->stateName;
        }
        else if ($type == 'City')
        {
            foreach ($this->cities as $city)
            {
                if ($city->getId() == $locationId)
                {
                    return $city->getFullName();
                }
            }
        }
        else if ($type == 'School')
        {
            foreach ($this->cities as $city)
            {
                foreach ($city->getSchools() as $school)
                {
                    if ($school->getId() == $locationId)
                    {
                        return $school->getFullName();
                    }
                }
            }
        }
    }

    public function setTimezone($timezone)
    {
        // noop
    }

    public function getTimezone($cityId)
    {
        foreach ($this->cities as $city)
        {
            if ($city->getId() == $cityId)
            {
                return $city->getTimezone();
            }
        }
    }

    public function setCityTimezone($cityName, $timezone)
    {
        $city = $this->getCity($cityName);
        if ($city)
        {
            $city->setTimezone($timezone);
        }
    }

    private $states = array('AL'=>"Alabama",
                            'AK'=>"Alaska",
                            'AZ'=>"Arizona",
                            'AR'=>"Arkansas",
                            'CA'=>"California",
                            'CO'=>"Colorado",
                            'CT'=>"Connecticut",
                            'DE'=>"Delaware",
                            'DC'=>"District Of Columbia",
                            'FL'=>"Florida",
                            'GA'=>"Georgia",
                            'HI'=>"Hawaii",
                            'ID'=>"Idaho",
                            'IL'=>"Illinois",
                            'IN'=>"Indiana",
                            'IA'=>"Iowa",
                            'KS'=>"Kansas",
                            'KY'=>"Kentucky",
                            'LA'=>"Louisiana",
                            'ME'=>"Maine",
                            'MD'=>"Maryland",
                            'MA'=>"Massachusetts",
                            'MI'=>"Michigan",
                            'MN'=>"Minnesota",
                            'MS'=>"Mississippi",
                            'MO'=>"Missouri",
                            'MT'=>"Montana",
                            'NE'=>"Nebraska",
                            'NV'=>"Nevada",
                            'NH'=>"New Hampshire",
                            'NJ'=>"New Jersey",
                            'NM'=>"New Mexico",
                            'NY'=>"New York",
                            'NC'=>"North Carolina",
                            'ND'=>"North Dakota",
                            'OH'=>"Ohio",
                            'OK'=>"Oklahoma",
                            'OR'=>"Oregon",
                            'PA'=>"Pennsylvania",
                            'RI'=>"Rhode Island",
                            'SC'=>"South Carolina",
                            'SD'=>"South Dakota",
                            'TN'=>"Tennessee",
                            'TX'=>"Texas",
                            'UT'=>"Utah",
                            'VT'=>"Vermont",
                            'VA'=>"Virginia",
                            'WA'=>"Washington",
                            'WV'=>"West Virginia",
                            'WI'=>"Wisconsin",
                            'WY'=>"Wyoming",
                            'OS'=>"Outer Space");
}

/**
 * @MongoDB\EmbeddedDocument
 */
class City
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $state;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fullName;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index(order="asc")
     */
    protected $slug;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @MongoDB\EmbedMany(targetDocument="School")
     */
    protected $schools;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $timezone;

    public function __construct()
    {
        $this->status = 'Active';
        $this->schools = array();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->setFullName();
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFullName()
    {
        $this->fullName = $this->name.', '.$this->state;
        $this->slug = new SlugNormalizer($this->fullName);
        $this->slug = $this->slug->__toString();

        foreach ($this->schools as $school)
        {
            $school->setFullName($this->state, $this->name);
        }
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    public function setSchools($schools)
    {
        $this->schools = $schools;
    }

    public function getSchools()
    {
        return $this->schools;
    }

    public function addSchool($name)
    {
        $school = $this->getSchool($name);
        if (!$this->schools || !$school)
        {
            $school = new School();
            $school->setName($name);
            $school->setFullName($this->state, $this->name);
            $this->schools[] = $school;
            $sorter = new ArraySorter();
            $this->schools = $sorter->sortBySubkey($this->schools, 'slug', SORT_ASC);
        }
        else
        {
            $school->setStatus('Active');
        }
    }

    public function removeSchool($schoolId)
    {
        foreach ($this->schools as $key => $school)
        {
            if ($school->getId() == $schoolId)
            {
                unset($this->schools[$key]);
            }
        }
    }

    public function getSchool($name)
    {
        $slug = new SlugNormalizer($name);
        foreach ($this->schools as $school)
        {
            if ($school->getSlug() == $slug)
            {
                return $school;
            }
        }

        return false;
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

/**
 * @MongoDB\EmbeddedDocument
 */
class School
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $name;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $fullName;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @MongoDB\Field(type="string")
     * @MongoDB\Index(order="asc")
     */
    protected $slug;

    public function __construct()
    {
        $this->status = 'Active';
    }

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setFullName($state, $city)
    {
        $this->fullName = $this->name.', '.$state;
        $this->slug = new SlugNormalizer($this->fullName);
        $this->slug = $this->slug->__toString();
    }

    public function getFullName()
    {
        return $this->fullName;
    }

    public function getSlug()
    {
        return $this->slug;
    }
}