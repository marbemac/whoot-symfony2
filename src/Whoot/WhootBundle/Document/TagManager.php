<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class TagManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createTag()
    {
        return $this->createObject();
    }

    public function deleteTag(Tag $tag, $andFlush = true)
    {
        return $this->deleteObject($tag, $andFlush);
    }

    public function updateTag(Tag $tag, $andFlush = true)
    {
        return $this->updateObject($tag, $andFlush);
    }

    public function findTagBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findTagsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }
}
