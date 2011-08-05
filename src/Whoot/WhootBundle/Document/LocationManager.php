<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class LocationManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createLocation()
    {
        return $this->createObject();
    }

    public function deleteLocation(Location $location, $andFlush = true)
    {
        return $this->deleteObject($location, $andFlush);
    }

    public function updateLocation(Location $location, $andFlush = true)
    {
        return $this->updateObject($location, $andFlush);
    }

    public function findLocationBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findLocationsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function deleteCity($cityId)
    {
        $this->m->Location->update(
            array('cities._id' => new \MongoId($cityId)),
            array('$set' =>
                array('cities.$.status' => 'Deleted')
            )
        );
    }

    public function deleteSchool($schoolId)
    {
        $location = $this->findLocationBy(array('cities.schools._id' => new \MongoId($schoolId)));
        $location->setSchoolStatus($schoolId, 'Deleted');
        $this->updateLocation($location);
    }

    public function addSchool($cityId, $school)
    {
        $location = $this->findLocationBy(array('cities._id' => new \MongoId($cityId)));

        if ($location)
        {
            $location->addSchool($cityId, $school);
            $this->updateLocation($location);
        }
    }

    public function updateCurrentLocation($target, $location)
    {
        $parts = explode('-', $location);
        if (count($parts) == 1)
        {
            $type = 'State';
            $location = $this->findLocationBy(array('id' => new \MongoId($parts[0])));
        }
        else if (count($parts) == 2) {
            $type = 'City';
            $location = $this->findLocationBy(array('cities._id' => new \MongoId($parts[1])));
        }
        else if (count($parts) == 3) {
            $type = 'School';
            $location = $this->findLocationBy(array('cities.schools._id' => new \MongoId($parts[2])));
        }

        if ($location)
        {
            $target->updateCurrentLocation($location, $parts, $type);
        }

        return $target;
    }
}
