<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="location")
 */
class Location
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $cityName
     * @ORM\Column(type="string", name="city_name")
     */
    protected $cityName;

    /**
     * @var string $stateCode
     * @ORM\Column(type="string", name="state_code")
     */
    protected $stateCode;

    /**
     * @var string $stateName
     * @ORM\Column(type="string", name="state_name")
     */
    protected $stateName;

    /**
     * @var Whoot\WhootUserBundle\Entity\User
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootUserBundle\Entity\User", mappedBy="location")
     */
    protected $users;

    /**
     * @var Whoot\WhootBundle\Entity\Post
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Post", mappedBy="location")
     */
    protected $posts;

    /**
     * @var Whoot\WhootBundle\Entity\Invite
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\Invite", mappedBy="location")
     */
    protected $invites;

    public function __construct() {
        $this->users = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->cityName.', '.$this->getStateCode();
    }

    /**
     * @param string $cityName
     * @return void
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
     * @return void
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
     * @return void
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
     * Get users connected to this location.
     *
     * @return array[User] $users
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param User $user
     *
     * @return void
     */
    public function setUser($user)
    {
        $this->users[] = $user;
    }

    /**
     * Get posts connected to this location.
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
     * Get invites connected to this location.
     *
     * @return array[Invite] $invites
     */
    public function getInvites()
    {
        return $this->invites;
    }

    /**
     * @param Invite $invite
     *
     * @return void
     */
    public function setInvite($invite)
    {
        $this->invites[] = $invite;
    }
}