<?php

namespace Whoot\WhootBundle\Model;

use Doctrine\ODM\MongoDB\DocumentManager;

class ObjectManager
{
    protected $dm;
    protected $repository;
    protected $class;

    public function __construct(DocumentManager $dm, $class)
    {
        $this->dm = $dm;
        $this->repository = $dm->getRepository($class);

        $metadata = $dm->getClassMetadata($class);
        $this->class = $metadata->name;
    }

    public function createObject()
    {
        $class = $this->class;
        $object = new $class;
        return $object;
    }

    public function deleteObject(ObjectInterface $object, $andFlush = true)
    {
        $object->setStatus('Deleted');

        if ($andFlush)
        {
            $this->dm->flush();
        }

        return $object;
    }

    public function updateObject(ObjectInterface $object, $andFlush = true)
    {
        $this->dm->persist($object);

        if ($andFlush)
        {
            $this->dm->flush();
        }
        
        return $object;
    }

    public function findObjectBy(array $criteria)
    {
        $qb = $this->dm->createQueryBuilder($this->class);

        foreach ($criteria as $field => $val)
        {
            $qb->field($field)->equals($val);
        }

        $query = $qb->getQuery();

        return $query->getSingleResult();
    }

    public function findObjectsBy(array $criteria, array $inCriteria, array $sorts, $dateRange, $limit, $offset)
    {
        $qb = $this->dm->createQueryBuilder($this->class);

        foreach ($criteria as $field => $val)
        {
            $qb->field($field)->equals($val);
        }

        foreach ($inCriteria as $field => $vals)
        {
            $vals = is_array($vals) ? $vals : array();
            $qb->field($field)->in($vals);
        }

        foreach ($sorts as $field => $order)
        {
            $qb->sort($field, $order);
        }

        if ($dateRange)
        {
            if (isset($dateRange['start']))
            {
                $qb->field($dateRange['target'])->gte(new \MongoDate(strtotime($dateRange['start'])));
            }

            if (isset($dateRange['end']))
            {
                $qb->field($dateRange['target'])->lte(new \MongoDate(strtotime($dateRange['end'])));
            }
        }

        if ($limit !== null && $offset !== null)
        {
            $qb->limit($limit)
               ->skip($offset);
        }

        $query = $qb->getQuery();

        return $query->execute();
    }
}
