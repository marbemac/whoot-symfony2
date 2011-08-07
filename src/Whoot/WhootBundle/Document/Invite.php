<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Marbemac\VoteBundle\Document\VotableInterface;
use Whoot\WhootBundle\Document\Location;

/**
 * @MongoDB\Document
 */
class Invite implements ObjectInterface
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
     *
     * @ASSERT\NotBlank()
     * @ASSERT\Choice(
     *     choices = {"working", "low_in", "low_out", "big_out"},
     *     message = "Choose a valid activity."
     * )
     */
    protected $type;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $image;

    /**
     * @MongoDB\Field(type="int")
     * @MongoDB\Index(order="asc")
     */
    protected $attendingCount;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $description;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $venue;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $time;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $address;

    /** @MongoDB\EmbedOne(targetDocument="Whoot\WhootBundle\Document\Coordinates") */
    protected $coordinates;

    /**
     * @MongoDB\Field(type="date")
     */
    protected $updatedAt;

    /**
     * @MongoDB\Field(type="date")
     * @MongoDB\Index(order="asc")
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
     * @MongoDB\EmbedOne(targetDocument="Whoot\WhootBundle\Document\CurrentLocation")
     */
    protected $currentLocation;

    /**
     * @MongoDB\field(type="hash")
     */
    protected $attending;

    protected $attendees;

    public function __construct() {
        $this->status = 'Active';
        $this->attendingCount = 0;
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

    public function setAttendingCount($count)
    {
        $this->attendingCount = $count;
    }

    public function getAttendingCount()
    {
        return $this->attendingCount;
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
     * @return ObjectId $currentLocation
     */
    public function getCurrentLocation()
    {
        return $this->currentLocation;
    }

    public function setCurrentLocation($location)
    {
        // noop function because the form system looks for this automatically. Dumb.
    }

    /**
     * @param Current $currentLocation
     */
    public function updateCurrentLocation($location, $locationIds, $type)
    {
        $currentLocation = new CurrentLocation($locationIds);
        $currentLocation->setType($type);
        $currentLocation->setName($location->buildName($locationIds[count($locationIds)-1], $type));
        $this->currentLocation = $currentLocation;
    }

    public function findAttendee($userId)
    {
        $userId = is_object($userId) ? $userId->__toString() : $userId;
        if ($this->attending)
        {
            return isset($this->attending[$userId]) ? $this->attending[$userId] : false;
        }

        return false;
    }

    public function getAttending()
    {
        $attending = array();
        if ($this->attending)
        {
            foreach ($this->attending as $userId)
            {
                $attending[] = new \MongoId($userId);
            }
        }

        return $attending;
    }

    public function setAttendees($attendees)
    {
        $this->attendees = $attendees;
    }

    public function getAttendees()
    {
        return $this->attendees;
    }

    /**
     * Set coordinates
     *
     * @param float $latitude
     * @param float $longitude
     */
    public function setCoordinates($latitude=null, $longitude=null)
    {
        if ($latitude == null || $longitude == null)
        {
            return;
        }
        
        $coordinates = new Coordinates();
        $coordinates->setLatitude($latitude);
        $coordinates->setLongitude($longitude);
        $this->coordinates = $coordinates;
    }

    /**
     * Get coordinates
     *
     * @return Coordinates $coordinates
     */
    public function getCoordinates()
    {
        return $this->coordinates;
    }

    public function setImage($groupId)
    {
        $this->image = $groupId;
    }

    public function getImage()
    {
        return $this->image;
    }

    /**
     * Set venue
     *
     * @param string $venue
     */
    public function setVenue($venue)
    {
        $this->venue = $venue;
    }

    /**
     * Get venue
     *
     * @return string $venue
     */
    public function getVenue()
    {
        return $this->venue;
    }

    /**
     * Set time
     *
     * @param string $time
     */
    public function setTime($time)
    {
        $this->time = $time;
    }

    /**
     * Get time
     *
     * @return string $time
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * Set address
     *
     * @param string $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * Get address
     *
     * @return string $address
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
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