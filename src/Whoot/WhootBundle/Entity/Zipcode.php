<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="zip_code")
 */
class Zipcode
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string $zipcode
     * @ORM\Column(type="string", name="zip_code")
     */
    protected $zipcode;

    /**
     * @var float $lat
     * @ORM\Column(type="float")
     */
    protected $lat;

    /**
     * @var float $lon
     * @ORM\Column(type="float")
     */
    protected $lon;

    /**
     * @var string $city
     * @ORM\Column(type="string")
     */
    protected $city;

    /**
     * @var string $statePrefix
     * @ORM\Column(type="string", name="state_prefix")
     */
    protected $statePrefix;

    /**
     * @var string $county
     * @ORM\Column(type="string")
     */
    protected $county;

    /**
     * @var string $z_type
     * @ORM\Column(type="string", name="z_type")
     */
    protected $zType;

    /**
     * @var float $xaxis
     * @ORM\Column(type="float")
     */
    protected $xaxis;

    /**
     * @var float $yaxis
     * @ORM\Column(type="float")
     */
    protected $yaxis;

    /**
     * @var float $zaxis
     * @ORM\Column(type="float")
     */
    protected $zaxis;    

    /**
     * @var string $zPrimary
     * @ORM\Column(type="string", name="z_primary")
     */
    protected $zPrimary;

    /**
     * @var string $worldRegion
     * @ORM\Column(type="string", name="worldregion")
     */
    protected $worldRegion;

    /**
     * @var string $country
     * @ORM\Column(type="string")
     */
    protected $country;

    /**
     * @var string $locationText
     * @ORM\Column(type="string", name="locationtext")
     */
    protected $locationText;

    /**
     * @var string $location
     * @ORM\Column(type="string")
     */
    protected $location;

    /**
     * @var string $population
     * @ORM\Column(type="string")
     */
    protected $population;

    /**
     * @var integer $housingUnits
     * @ORM\Column(type="integer", name="housingunits")
     */
    protected $housingUnits;

    /**
     * @var integer $income
     * @ORM\Column(type="integer")
     */
    protected $income;

    /**
     * @var string $landArea
     * @ORM\Column(type="string", name="landarea")
     */
    protected $landArea;

    /**
     * @var string $waterArea
     * @ORM\Column(type="string", name="waterarea")
     */
    protected $waterArea;

    /**
     * @var string $decommisioned
     * @ORM\Column(type="string")
     */
    protected $decommisioned;

    /**
     * @var string $militaryRestrictionCodes
     * @ORM\Column(type="string", name="militaryrestrictioncodes")
     */
    protected $militaryRestrictionCodes;

    /**
     * @var string $decommisionedPlace
     * @ORM\Column(type="string", name="decommisionedplace")
     */
    protected $decommisionedPlace;

    public function __construct() {
    }

    /**
     * @param string $city
     * @return void
     */
    public function setCity($city)
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $country
     * @return void
     */
    public function setCountry($country)
    {
        $this->country = $country;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $county
     * @return void
     */
    public function setCounty($county)
    {
        $this->county = $county;
    }

    /**
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * @param string $decommisioned
     * @return void
     */
    public function setDecommisioned($decommisioned)
    {
        $this->decommisioned = $decommisioned;
    }

    /**
     * @return string
     */
    public function getDecommisioned()
    {
        return $this->decommisioned;
    }

    /**
     * @param string $decommisionedPlace
     * @return void
     */
    public function setDecommisionedPlace($decommisionedPlace)
    {
        $this->decommisionedPlace = $decommisionedPlace;
    }

    /**
     * @return string
     */
    public function getDecommisionedPlace()
    {
        return $this->decommisionedPlace;
    }

    /**
     * @param int $housingUnits
     * @return void
     */
    public function setHousingUnits($housingUnits)
    {
        $this->housingUnits = $housingUnits;
    }

    /**
     * @return int
     */
    public function getHousingUnits()
    {
        return $this->housingUnits;
    }

    /**
     * @param int $income
     * @return void
     */
    public function setIncome($income)
    {
        $this->income = $income;
    }

    /**
     * @return int
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * @param string $landArea
     * @return void
     */
    public function setLandArea($landArea)
    {
        $this->landArea = $landArea;
    }

    /**
     * @return string
     */
    public function getLandArea()
    {
        return $this->landArea;
    }

    /**
     * @param float $lat
     * @return void
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    /**
     * @return float
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * @param string $location
     * @return void
     */
    public function setLocation($location)
    {
        $this->location = $location;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param string $locationText
     * @return void
     */
    public function setLocationText($locationText)
    {
        $this->locationText = $locationText;
    }

    /**
     * @return string
     */
    public function getLocationText()
    {
        return $this->locationText;
    }

    /**
     * @param float $lon
     * @return void
     */
    public function setLon($lon)
    {
        $this->lon = $lon;
    }

    /**
     * @return float
     */
    public function getLon()
    {
        return $this->lon;
    }

    /**
     * @param string $militaryRestrictionCodes
     * @return void
     */
    public function setMilitaryRestrictionCodes($militaryRestrictionCodes)
    {
        $this->militaryRestrictionCodes = $militaryRestrictionCodes;
    }

    /**
     * @return string
     */
    public function getMilitaryRestrictionCodes()
    {
        return $this->militaryRestrictionCodes;
    }

    /**
     * @param string $population
     * @return void
     */
    public function setPopulation($population)
    {
        $this->population = $population;
    }

    /**
     * @return string
     */
    public function getPopulation()
    {
        return $this->population;
    }

    /**
     * @param string $statePrefix
     * @return void
     */
    public function setStatePrefix($statePrefix)
    {
        $this->statePrefix = $statePrefix;
    }

    /**
     * @return string
     */
    public function getStatePrefix()
    {
        return $this->statePrefix;
    }

    /**
     * @param string $waterArea
     * @return void
     */
    public function setWaterArea($waterArea)
    {
        $this->waterArea = $waterArea;
    }

    /**
     * @return string
     */
    public function getWaterArea()
    {
        return $this->waterArea;
    }

    /**
     * @param string $worldRegion
     * @return void
     */
    public function setWorldRegion($worldRegion)
    {
        $this->worldRegion = $worldRegion;
    }

    /**
     * @return string
     */
    public function getWorldRegion()
    {
        return $this->worldRegion;
    }

    /**
     * @param float $xaxis
     * @return void
     */
    public function setXaxis($xaxis)
    {
        $this->xaxis = $xaxis;
    }

    /**
     * @return float
     */
    public function getXaxis()
    {
        return $this->xaxis;
    }

    /**
     * @param float $yaxis
     * @return void
     */
    public function setYaxis($yaxis)
    {
        $this->yaxis = $yaxis;
    }

    /**
     * @return float
     */
    public function getYaxis()
    {
        return $this->yaxis;
    }

    /**
     * @param string $zPrimary
     * @return void
     */
    public function setZPrimary($zPrimary)
    {
        $this->zPrimary = $zPrimary;
    }

    /**
     * @return string
     */
    public function getZPrimary()
    {
        return $this->zPrimary;
    }

    /**
     * @param string $zType
     * @return void
     */
    public function setZType($zType)
    {
        $this->zType = $zType;
    }

    /**
     * @return string
     */
    public function getZType()
    {
        return $this->zType;
    }

    /**
     * @param float $zaxis
     * @return void
     */
    public function setZaxis($zaxis)
    {
        $this->zaxis = $zaxis;
    }

    /**
     * @return float
     */
    public function getZaxis()
    {
        return $this->zaxis;
    }

    /**
     * @param string $zipcode
     * @return void
     */
    public function setZipcode($zipcode)
    {
        $this->zipcode = $zipcode;
    }

    /**
     * @return string
     */
    public function getZipcode()
    {
        return $this->zipcode;
    }
}