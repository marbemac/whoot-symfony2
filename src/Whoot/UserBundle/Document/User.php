<?php
namespace Whoot\UserBundle\Document;

use FOS\UserBundle\Document\User as BaseUser;
use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Document\Post;
use Doctrine\Common\Collections\ArrayCollection;
use Whoot\WhootBundle\Util\SlugNormalizer;

/**
 * @MongoDB\Document
 */
class User extends BaseUser
{
    /** @MongoDB\Id */
    protected $id;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $status;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $currentProfileImage;
    
    /**
     * @MongoDB\Field(type="string")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\MinLength(2)
     */
    protected $firstName;
    
    /**
     * @MongoDB\Field(type="string")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\MinLength(2)
     */    
    protected $lastName;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $nameSlug;

    /**
     * @MongoDB\Field(type="string")
     * 
     * @ASSERT\Choice(
     *     choices = {"m", "f"},
     *     message = "Choose a valid gender."
     * )
     */
    protected $gender;

    /**
     * @MongoDB\Field(type="string")
     */
    protected $facebookId;

    /**
     * @MongoDB\Field(type="int")
     */
    protected $score;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $currentLocation;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $location;

    /**
     * @MongoDB\EmbedOne(targetDocument="CurrentPost")
     */
    protected $currentPost;

    /**
     * @MongoDB\Field(type="hash")
     */
    protected $following;

    /**
     * @MongoDB\EmbedOne(targetDocument="DailyPing")
     */
    protected $dailyPings;

    public function __construct() {
        parent::__construct();
        $this->status = 'Active';
        $this->score = 0;
        $this->following = array();
    }

    /**
     * @return integer $id
     */
    public function getId()
    {
        return new \MongoId($this->id);
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
     * @param ObjectId $currentProfileImage
     */
    public function setProfileImage($currentProfileImage)
    {
        $this->currentProfileImage = $currentProfileImage;
    }

    /**
     * @return ObjectId $currentProfileImage
     */
    public function getCurrentProfileImage()
    {
        return $this->currentProfileImage;
    }

    /**
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        $this->nameSlug = new SlugNormalizer($this->firstName.' '.$this->lastName);
    }

    /**
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        $this->nameSlug = new SlugNormalizer($this->firstName.' '.$this->lastName);
    }

    /**
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    public function getNameSlug()
    {
        return $this->nameSlug;
    }

    /*
     * Returns a users full name, capitalized.
     */
    public function getFullName()
    {
        return ucwords($this->firstName.'  '.$this->lastName);
    }

    /**
     * @param string $gender
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    /**
     * @return string $gender
     */
    public function getGender()
    {
        return $this->gender;
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

    /**
     * @return ObjectId $location
     */
    public function getLocation()
    {
        return new \MongoId($this->location);
    }

    /**
     * @param ObjectId $location
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return ObjectId $currentLocation
     */
    public function getCurrentLocation()
    {
        return $this->currentLocation ? new \MongoId($this->currentLocation) : $this->getLocation();
    }

    /**
     * @param Current $currentLocation
     */
    public function setCurrentLocation($currentLocation)
    {
        $this->currentLocation = $currentLocation;
    }

    public function setCurrentPost(Post $post)
    {
        $currentPost = new CurrentPost();
        $currentPost->setPost($post);
        $this->currentPost = $currentPost;
    }

    public function getCurrentPost()
    {
        return $this->currentPost;
    }

    public function setFollowing(array $following)
    {
        $this->following = $following;
    }

    public function addFollowing($following)
    {
        if (!in_array($following, $this->following, true)) {
            $this->following[] = $following;
        }
    }

    public function getFollowing()
    {
        $following = array();
        foreach ($this->following as $id)
        {
            $following[] = new \MongoId($id);
        }
        return $following;
    }

    public function removeFollowing($key)
    {
        unset($this->following[$key]);
    }

    /**
     * @param string $facebookID
     */
    public function setFacebookId($facebookID)
    {
        $this->facebookId = $facebookID;
        $this->setUsername($facebookID);
        $this->salt = '';
    }

    /**
     * @return string
     */
    public function getFacebookId()
    {
        return $this->facebookId;
    }

    public function hasPinged($userId)
    {
        $date = date('Y-m-d', time());
        if ($this->dailyPings && $this->dailyPings->getDateGroup() == $date && in_array($userId, $this->dailyPings->getPings()))
        {
            return true;
        }
        else
        {
            return false;
        }
    }

    public function addPing($userId)
    {
        $date = date('Y-m-d', time());
        if (!$this->dailyPings || $this->dailyPings->getDateGroup() != $date)
        {
            $dailyPing = new DailyPing();
            $dailyPing->setDateGroup($date);
            $dailyPing->addPing($userId);
            $this->dailyPings = $dailyPing;
        }
        else
        {
            $this->dailyPings->addPing($userId);
        }
    }

    public function removePing($userId)
    {
        $date = date('Y-m-d', time());
        if ($this->dailyPings && $this->dailyPings->getDateGroup() == $date)
        {
            $this->dailyPings->removePing($userId);
        }
    }

    /**
     * @param Array
     */
    public function setFBData($fbdata)
    {
        if (isset($fbdata['id'])) {
            $this->setFacebookId($fbdata['id']);
            $this->addRole('ROLE_FACEBOOK');
        }
        if (isset($fbdata['first_name'])) {
            $this->setFirstname($fbdata['first_name']);
        }
        if (isset($fbdata['last_name'])) {
            $this->setLastname($fbdata['last_name']);
        }
        if (isset($fbdata['email'])) {
            $this->setEmail($fbdata['email']);
        }
        if (isset($fbdata['gender'])) {
            $this->setGender($fbdata['gender']);
        }
    }
}

/**
 * @MongoDB\EmbeddedDocument
 */
class CurrentPost
{
    /**
     * @MongoDB\Field(type="date")
     */
    protected $createdAt;

    /**
     * @MongoDB\Field(type="object_id")
     */
    protected $post;

    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setPost($post)
    {
        $this->post = $post->getId();
        $this->createdAt = $post->getCreatedAt() ? $post->getCreatedAt() : new \Date();
    }

    public function getPost()
    {
        return new \MongoId($this->post);
    }
}

/**
 * @MongoDB\EmbeddedDocument
 */
class DailyPing
{
    /**
     * @MongoDB\Field(type="string")
     */
    protected $dateGroup;

    /**
     * @MongoDB\Field(type="hash")
     */
    protected $pings;

    public function __construct()
    {
        $this->pings = array();
    }

    public function setDateGroup($date)
    {
        $this->dateGroup = $date;
    }

    public function getDateGroup()
    {
        return $this->dateGroup;
    }

    public function addPing($userId)
    {
        if (!in_array($userId, $this->pings, true)) {
            $this->pings[] = $userId;
        }
    }

    public function getPings()
    {
        $pings = array();
        foreach ($this->pings as $id)
        {
            $pings[] = new \MongoId($id);
        }
        return $pings;
    }

    public function removePing($userId)
    {
        $key = array_search($userId, $this->pings);
        unset($this->pings[$key]);
    }
}