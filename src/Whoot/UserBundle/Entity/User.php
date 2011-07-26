<?php

namespace Whoot\UserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use FOS\UserBundle\Entity\User as BaseUser;

use Whoot\WhootBundle\Entity\Post;

/**
 * @ORM\Entity
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $status
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @var string $profileImage
     * @ORM\Column(type="string", name="profile_image", nullable=true)
     */
    protected $profileImage;
    
    /**
     * @var string $firstName
     * @ORM\Column(type="string", length="255", name="first_name")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\MinLength(2)
     */
    private $firstName;
    
    /**
     * @var string $lastName
     * @ORM\Column(type="string", length="255", name="last_name")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\MinLength(2)
     */    
    private $lastName;

    /**
     * @var string $gender
     * @ORM\Column(type="string", length="2")
     * 
     * @ASSERT\Choice(
     *     choices = {"m", "f"},
     *     message = "Choose a valid gender."
     * )
     */
    private $gender;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length="255", name="facebook_id", nullable=true)
     */
    protected $facebookId;

    /**
     * @var integer $score
     * @ORM\Column(type="integer")
     */
    protected $score = 0;

    /**
     * @var Location
     *
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Location", inversedBy="users")
     */
    protected $location;

    /**
     * @var $posts
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Post", mappedBy="createdBy", cascade={"persist"})
     */
    protected $posts;

    /**
     * @var $invites
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Invite", mappedBy="createdBy", cascade={"persist"})
     */
    protected $invites;

    /**
     * @var UserFollowing
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\UserFollowing", mappedBy="user")
     */
    protected $following;

    /**
     * @var UserFollowing
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\UserFollowing", mappedBy="following")
     */
    protected $followers;

    /**
     * @var UserList
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\UserLList", mappedBy="user")
     */
    protected $lists;

    public function __construct() {
        $this->posts = new ArrayCollection();
        $this->invites = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->lists = new ArrayCollection();
        $this->status = 'Active';
        parent::__construct();
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
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
     * Set profileImage
     *
     * @param string $profileImage
     */
    public function setProfileImage($profileImage)
    {
        $this->profileImage = $profileImage;
    }

    /**
     * Get profileImage
     *
     * @return string $profileImage
     */
    public function getProfileImage()
    {
        return $this->profileImage;
    }

    /**
     * Set firstName
     *
     * @param string $firstName
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    /**
     * Get firstName
     *
     * @return string $firstName
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * Set lastName
     *
     * @param string $lastName
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    /**
     * Get lastName
     *
     * @return string $lastName
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /*
     * Returns a users full name, capitalized.
     */
    public function getFullName()
    {
        return ucwords($this->firstName.'  '.$this->lastName);
    }

    /**
     * Set gender
     *
     * @param string $gender
     */
    public function setGender($gender)
    {
        if (in_array($gender, array('male', 'm', 'man')))
        {
            $this->gender = 'm';
        }
        else if (in_array($gender, array('female', 'f', 'girl', 'woman')))
        {
            $this->gender = 'f';
        }
    }

    /**
     * Get gender
     *
     * @return string $gender
     */
    public function getGender()
    {
        return $this->gender;
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

    /**
     * Get a user's location.
     *
     * @return Location $location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param Location $location
     *
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * Get a user's posts.
     *
     * @return array[Post] $posts
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param Post $post
     *
     * @return void
     */
    public function setPost($post)
    {
        $this->posts[] = $post;
    }

    /**
     * Get a user's invites.
     *
     * @return array[Invite] $invites
     */
    public function getInvites()
    {
        return $this->invites;
    }

    /**
     * @param Post $invite
     *
     * @return void
     */
    public function setInvite($invite)
    {
        $this->invites[] = $invite;
    }

    /**
     * @param string $facebookID
     * @return void
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