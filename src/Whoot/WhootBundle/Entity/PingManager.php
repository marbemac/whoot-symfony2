<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\Ping;

class PingManager
{
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
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
                $response['status'] = 'new';
                $ping->setStatus('Active');
                $this->updatePing($ping);
            }
            else
            {
                $response['status'] = 'deleted';
                $this->deletePing($ping);
            }
        }
        else
        {
            $fromUser = $this->em->getRepository('WhootBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('WhootBundle:User')->find($toUser);

            $response['status'] = 'new';

            $ping = $this->createPing();

            $ping->setCreatedBy($fromUser);
            $ping->setUser($toUser);
            $this->updatePing($ping);
        }

        return $response;
    }

    public function findPingsBy($toUser, $since)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p'))
           ->from('Whoot\WhootBundle\Entity\Ping', 'p')
           ->where('p.user = :toUser AND p.status = :status')
           ->setParameters(array(
               'toUser' => $toUser,
               'status' => 'Active'
           ));

        if ($since)
        {
            $qb->andWhere('p.createdAt >= :since');
            $qb->setParameter('since', $since);
        }

        $query = $qb->getQuery();
        return $query->getResult(Query::HYDRATE_ARRAY);
    }
}
