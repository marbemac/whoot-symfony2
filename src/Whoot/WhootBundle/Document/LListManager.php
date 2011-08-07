<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class LListManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createLList()
    {
        return $this->createObject();
    }

    public function deleteLList(LList $llist, $andFlush = true)
    {
        return $this->deleteObject($llist, $andFlush);
    }

    public function updateLList(LList $llist, $andFlush = true)
    {
        return $this->updateObject($llist, $andFlush);
    }

    public function findLListBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findLListsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function addUser($list, $userId)
    {
        $this->m->LList->update(
            array('_id' => $list->getId()),
            array(
                '$inc' =>
                    array(
                        'userCount' => 1
                    ),
                '$set' =>
                    array(
                        'users.'.$userId => new \MongoId($userId),
                    )
            )
        );
    }

    public function removeUser($list, $userId)
    {
        $this->m->LList->update(
            array('_id' => $list->getId()),
            array(
                '$inc' =>
                    array(
                        'userCount' => -1
                    ),
                '$unset' =>
                    array(
                        'users.'.$userId => 1,
                    )
            )
        );
    }
}
