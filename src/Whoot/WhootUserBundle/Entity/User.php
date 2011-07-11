<?php

namespace Whoot\WhootUserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use FOS\UserBundle\Entity\User as BaseUser;

use Whoot\WhootBundle\Entity\Post;
use Whoot\WhootBundle\Entity\UsersPosts;

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
     *     choices = {"m1", "m2", "m3", "f1", "f2", "f3"},
     *     message = "Choose a valid gender."
     * )
     */
    private $gender;
    
    /**
     * @var string $zipcode
     * @ORM\Column(type="string", length="25")
     * 
     * @ASSERT\NotBlank()
     * @ASSERT\MinLength(5)
     * @ASSERT\Regex(
     *     pattern = "/\d+\",
     *     message = "Please input a valid zipcode."
     * )
     */
    private $zipcode;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length="255", name="facebook_id", nullable=true)
     */
    protected $facebookId;

    /**
     * @var UsersPosts
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\UsersPosts", mappedBy="user", cascade={"persist"})
     */
    protected $posts;

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
        $this->gender = $gender;
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
     * Set zipcode
     *
     * @param string $zipcode
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }

    /**
     * Get zipcode
     *
     * @return string $zipcode
     */
    public function getZipcode()
    {
        return $this->zipcode;
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
    public function setPost(Post $post)
    {
        $this->posts[] = $post;
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
    }
}