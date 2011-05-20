<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\UserFollowing;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;

class UserManager extends BaseUserManager
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

    public function getUser($userId)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->where('u.id = :userId')
           ->setParameters(array(
               'userId' => $userId
           ));

        $query = $qb->getQuery();
        $user = $query->getSingleResult(Query::HYDRATE_OBJECT);

        return $user;
    }

    public function getUsersBy($currentUser)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->where('u.status = :status AND u.id != :currentUser')
           ->setParameters(array(
               'status' => 'Active',
               'currentUser' => $currentUser
           ));

        $query = $qb->getQuery();
        $users = $query->getArrayResult();

        return $users;
    }

    /*
     * Check for a follow connection between two users. Return if found.
     *
     * @param integer $fromUser
     * @param integer $toUser
     */
    public function findFollowConnection($fromUser, $toUser, $returnObject = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('f'))
           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'f')
           ->where('f.user = :fromUser AND f.following = :toUser')
           ->setParameters(array(
               'fromUser' => $fromUser,
               'toUser' => $toUser
           ));

        $query = $qb->getQuery();
        $connection = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($connection[0]) ? $connection[0] : null;
    }

    public function toggleFollow($fromUser, $toUser)
    {
        $connection = $this->findFollowConnection($fromUser, $toUser, true);

        $response = array();

        if ($connection)
        {
            $response['status'] = 'existing';
            $this->em->remove($connection);
        }
        else
        {
            $fromUser = $this->em->getRepository('WhootBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('WhootBundle:User')->find($toUser);

            $response['status'] = 'new';

            $connection = new UserFollowing();
            $connection->setUser($fromUser);
            $connection->setFollowing($toUser);
            $this->em->persist($connection);
        }

        $this->em->flush();

        return $response;
    }

    public function findUndecided($since)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->leftJoin('u.posts', 'up', 'WITH', 'up.createdAt >= :since AND up.status = :status')
           ->having('count(up.id) = 0')
           ->groupBy('u.id')
           ->orderBy('u.firstName', 'ASC')
           ->addOrderBy('u.lastName', 'ASC')
           ->setParameters(array(
               'since' => $since,
               'status' => 'Active'
           ));

        $query = $qb->getQuery();
        $users = $query->getArrayResult();

        return $users;
    }

    /**
     * Get the # of followers and following for a user.
     *
     * @param  integer $userId
     * @return array $followingStats
     */
    public function getFollowingStats($userId)
    {
        $response = array();

        $qb = $this->em->createQueryBuilder();
        $qb->select(array('count(uf.id) AS cnt'))
           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'uf')
           ->where('uf.status = :status AND uf.user = :userId')
           ->setParameters(array(
               'userId' => $userId,
               'status' => 'Active'
           ));

        $query = $qb->getQuery();
        $response['following'] = $query->getArrayResult();

        $qb = $this->em->createQueryBuilder();
        $qb->select(array('count(uf.id) AS cnt'))
           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'uf')
           ->where('uf.status = :status AND uf.following = :userId')
           ->setParameters(array(
               'userId' => $userId,
               'status' => 'Active'
           ));

        $query = $qb->getQuery();
        $response['followers'] = $query->getArrayResult();

        return $response;
    }
}
