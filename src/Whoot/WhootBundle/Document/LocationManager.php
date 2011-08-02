<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;

class LocationManager
{
    protected $dm;

    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * {@inheritDoc}
     */
    public function createLocation()
    {
        $location = new Location();
        return $location;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteLocation(Location $location)
    {
        echo 'delete location';
        exit();
        $location->setStatus('Deleted');
        $this->dm->persist($location);
        $this->dm->flush();
        return array('result' => 'success');
    }

    /**
     * {@inheritDoc}
     */
    public function updateLocation(Location $location, $andFlush = true)
    {
        $this->dm->persist($location);

        if ($andFlush)
        {
            $this->dm->flush();
        }
    }

    /*
     * Find a location. Return if found.
     */
    public function findLocationBy(array $criteria, $returnObject = false)
    {
        $qb = $this->dm->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\Location', 'l');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('l.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);

        $query = $qb->getQuery();
        $location = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($location[0]) ? $location[0] : null;
    }

    public function findLocationsBy(array $criteria, $returnObject = false)
    {
        $qb = $this->dm->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\Location', 'l');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('l.'.$key.' = :'.$key)
               ->setParameter($key, $val);

        }
        $qb->orderBy('l.cityName', 'ASC');
        
        $query = $qb->getQuery();
        return $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
    }

    /*
     * Given a location string (from facebook or otherwise), split it at the ',' and
     * attempt to find and match it to one of our locations. If found, set the users location.
     */
    public function getFBLocation($location)
    {
        $locations = explode(',', $location);
        foreach ($locations as $key => $location)
        {
            $locations[$key] = trim($location);
        }

        $qb = $this->dm->createQueryBuilder();
        $qb->select(array('l'))
            ->from('Whoot\WhootBundle\Entity\Location', 'l')
            ->where(
                $qb->expr()->in('l.cityName', $locations)
            )
            ->andWhere(
                $qb->expr()->in('l.stateName', $locations)
            );

        $query = $qb->getQuery();
        $query->useResultCache(true, 300, 'location_search_'.$location);
        $results = $query->getResult();

        return count($results) > 0 ? $results[0] : null;
    }
}
