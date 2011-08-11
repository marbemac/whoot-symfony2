<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Symfony\Component\Validator\Constraints as Assert;
use Whoot\WhootBundle\Model\ObjectInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;
use Whoot\WhootBundle\Util\ArraySorter;

/** @MongoDB\EmbeddedDocument */
class Coordinates
{
    /** @MongoDB\Field(type="float") */
    protected $latitude;

    /** @MongoDB\Field(type="float") */
    protected $longitude;

    /**
     * Set latitude
     *
     * @param float $latitude
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;
    }

    /**
     * Get latitude
     *
     * @return float $latitude
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * Set longitude
     *
     * @param float $longitude
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;
    }

    /**
     * Get longitude
     *
     * @return float $longitude
     */
    public function getLongitude()
    {
        return $this->longitude;
    }
}