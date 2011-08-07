<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class PingManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createPing()
    {
        return $this->createObject();
    }

    public function deletePing(Ping $ping, $andFlush = true)
    {
        return $this->deleteObject($ping, $andFlush);
    }

    public function updatePing(Ping $ping, $andFlush = true)
    {
        return $this->updateObject($ping, $andFlush);
    }

    public function findPingBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findPingsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function addPing($fromUser, $toUserId, $andFlush = true)
    {
        $dateGroup = date('Y-m-d', time());
        $pinged = new \MongoId($toUserId);
        $pingGroup = $this->findPingBy(array('dateGroup' => $dateGroup, 'pinged' => $pinged));
        if ($pingGroup)
        {
            $pingGroup->addPinger($fromUser->getId());
        }
        else
        {
            $pingGroup = $this->createPing();
            $pingGroup->setDateGroup($dateGroup);
            $pingGroup->setPinged($pinged);
            $pingGroup->addPinger($fromUser->getId());
        }

        // Increment the # of pings the target user has received
        $this->m->User->update(
            array('_id' => new \MongoId($toUserId)),
            array(
                '$inc' =>
                    array(
                        'pingCount' => 1
                    )
            )
        );

        $this->updatePing($pingGroup, $andFlush);
    }
}
