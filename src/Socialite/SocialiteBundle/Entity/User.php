<?php

namespace Socialite\SocialiteBundle\Entity;

use FOS\UserBundle\Entity\User as BaseUser;

/**
 * @orm:Entity
 * @orm:Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @var integer $id
     * @orm:Id
     * @orm:Column(type="integer")
     * @orm:generatedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $firstName
     * @orm:Column(type="string", length="255")
     * 
     * @assert:NotBlank()
     * @assert:MinLength(2)
     */
    private $firstName;
    
    /**
     * @var string $lastName
     * @orm:Column(type="string", length="255")
     * 
     * @assert:NotBlank()
     * @assert:MinLength(2)
     */    
    private $lastName;

    /**
     * @var string $gender
     * @orm:Column(type="string", length="2")
     * 
     * @assert:Choice(
     *     choices = {"m1", "m2", "m3", "f1", "f2", "f3"},
     *     message = "Choose a valid gender."
     * )
     */
    private $gender;
    
    /**
     * @var string $zipcode
     * @orm:Column(type="string", length="25")
     * 
     * @assert:NotBlank()
     * @assert:MinLength(5)
     * @assert:Regex(
     *     pattern = "/\d+\",
     *     message = "Please input a valid zipcode."
     * )
     */
    private $zipcode;

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
}