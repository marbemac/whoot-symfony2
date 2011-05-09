<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Socialite\SocialiteBundle\Entity\UserFollowing;

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

    public function getUsersBy($currentUser)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Socialite\SocialiteBundle\Entity\User', 'u')
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
           ->from('Socialite\SocialiteBundle\Entity\UserFollowing', 'f')
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
            $fromUser = $this->em->getRepository('SocialiteBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('SocialiteBundle:User')->find($toUser);

            $response['status'] = 'new';

            $connection = new UserFollowing();
            $connection->setUser($fromUser);
            $connection->setFollowing($toUser);
            $this->em->persist($connection);
        }

        $this->em->flush();

        return $response;
    }
}
