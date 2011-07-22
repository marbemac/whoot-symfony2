<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Evario\NotificationBundle\Entity\NotificationManager;
use Whoot\WhootBundle\Entity\Ping;

class PingManager
{
    protected $em;
    protected $notificationManager;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EntityManager $em, NotificationManager $notificationManager)
    {
        $this->em = $em;
        $this->notificationManager = $notificationManager;
    }

    /**
     * {@inheritDoc}
     */
    public function createPing()
    {
        $ping = new Ping();
        return $ping;
    }

    /**
     * {@inheritDoc}
     */
    public function deletePing(Ping $ping)
    {
        $ping->setStatus('Deleted');
        $this->em->persist($ping);

        $this->notificationManager->removeNotification(array('affectedUser' => $ping->getUser(), 'type' => 'Ping'), $ping->getCreatedAt(), false);

        $this->em->flush();
        return array('result' => 'success');
    }

    /**
     * {@inheritDoc}
     */
    public function updatePing(Ping $ping, $andFlush = true)
    {
        $this->em->persist($ping);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    /*
     * Check for a ping between two users. Return if found.
     *
     * @param integer $fromUser
     * @param integer $toUser
     */
    public function findPing($fromUser, $toUser, $since=null, $returnObject = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p'))
           ->from('Whoot\WhootBundle\Entity\Ping', 'p')
           ->where('p.createdBy = :fromUser AND p.user = :toUser AND p.status = :status')
           ->setParameters(array(
               'fromUser' => $fromUser,
               'toUser' => $toUser,
               'status' => 'Active'
           ));

        if ($since)
        {
            $qb->andWhere('p.createdAt >= :since');
            $qb->setParameter('since', $since);
        }

        $query = $qb->getQuery();
        $ping = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($ping[0]) ? $ping[0] : null;
    }

    public function togglePing($fromUser, $toUser)
    {
        $ping = $this->findPing($fromUser, $toUser, date('Y-m-d 05:00:00', time()-(60*60*5)), true);

        $response = array();

        if ($ping)
        {
            if ($ping->getStatus() == 'Deleted')
            {
                $this->notificationManager->addNotification('Ping', $toUser, null, $ping->getCreatedAt(), false);
                $response['state'] = 'new';
                $ping->setStatus('Active');
                $this->updatePing($ping);
            }
            else
            {
                $response['state'] = 'deleted';
                $this->deletePing($ping);
            }
        }
        else
        {
            $fromUser = $this->em->getRepository('WhootUserBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('WhootUserBundle:User')->find($toUser);

            $response['state'] = 'new';

            $ping = $this->createPing();

            $ping->setCreatedBy($fromUser);
            $ping->setUser($toUser);

            $this->notificationManager->addNotification('Ping', $toUser, null, null, false);

            $this->updatePing($ping);
        }

        return $response;
    }

    public function findPingsBy(array $criteria, $dateRange, $since)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p'))
           ->from('Whoot\WhootBundle\Entity\Ping', 'p')
           ->where('p.status = :status')
           ->setParameters(array(
               'status' => 'Active'
           ));

        if ($dateRange)
        {
            $qb->andWhere('p.createdAt >= :dateFrom')
               ->andWhere('p.createdAt <= :dateTo')
               ->setParameters(array(
                                   'dateFrom' => $dateRange['from'],
                                   'dateTo' => $dateRange['to']
                               ));
        }

        if ($since)
        {
            $qb->andWhere('p.createdAt >= :since');
            $qb->setParameter('since', $since);
        }

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('p.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);

        $query = $qb->getQuery();
        return $query->getResult(Query::HYDRATE_ARRAY);
    }
}
