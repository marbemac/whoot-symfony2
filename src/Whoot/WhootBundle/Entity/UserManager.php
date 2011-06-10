<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\UserFollowing;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;

use Symfony\Component\Security\Core\User\UserProviderInterface;

class UserManager extends BaseUserManager implements UserProviderInterface
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

    public function getUser(array $criteria)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u');
        
        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('u.'.$key.' = :'.$key);
            $qb->setParameter($key, $val);
        }

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

    public function getFollowing($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
           ->where('u.status = :status')
           ->setParameters(array(
               'user' => $user,
               'status' => 'Active'
           ));
        $query = $qb->getQuery();
        $followingUsers = $query->getArrayResult();

        return $followingUsers;
    }

    public function getFollowers($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->innerJoin('u.following', 'f', 'WITH', 'f.following = :user AND f.status = :status')
           ->where('u.status = :status')
           ->setParameters(array(
               'user' => $user,
               'status' => 'Active'
           ));
        $query = $qb->getQuery();
        $followersUsers = $query->getArrayResult();

        return $followersUsers;
    }

    public function findUndecided($user, $since)
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

        if ($user)
        {
            $followingUsers = $this->getFollowing($user);

            // If they are not following anyone, there will be no undecided...
            if (count($followingUsers) == 0)
                return array();

            $following = array();
            foreach ($followingUsers as $followingUser)
            {
                $following[] = $followingUser['id'];
            }

            $qb->andwhere($qb->expr()->in('u.id', $following));
        }

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

    /**
     * Get location data from zipcode.
     *
     * @param  integer $zipcode
     *
     * @return array
     */
    public function getLocation($zipcode)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\Zipcode', 'l')
           ->where('l.zipcode = :zipcode')
           ->setParameters(array(
               'zipcode' => $zipcode
           ));

        $query = $qb->getQuery();
        $result = $query->getArrayResult();

        return isset($result[0]) ? $result[0] : null;
    }

    /*
     * Get users for a search query. Return followed users first.
     *
     * @param integer $userId
     * @param string $query
     */
    public function findForSearch($userId, $query)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u', 'up', 'p'))
           ->from('Whoot\WhootBundle\Entity\User', 'u')
           ->leftJoin('u.posts', 'up', 'WITH', 'up.status = :status AND up.createdAt >= :createdAt')
           ->leftJoin('up.post', 'p', 'WITH', 'p.status = :status')
           ->where(
               $qb->expr()->like('CONCAT(u.firstName, u.lastName)', ':query')
           )
           ->setParameters(array(
               'query' => '%'.$query.'%',
               'status' => 'Active',
                'createdAt'    => date('Y-m-d 05:00:00', time()-(60*60*5))
           ));

        $query = $qb->getQuery();
        $results = $query->getArrayResult();

        $response = array();
        foreach ($results as $result)
        {
            $response[] = array(
                'name' => $result['firstName'].' '.$result['lastName'],
                'username' => $result['username'],
                'id' => $result['id'],
                'profileImage' => $result['profileImage'] ? $result['profileImage'] : 'gravatar',
                'postId' => isset($result['posts'][0]) ? $result['posts'][0]['post']['id'] : null
            );
        }

        return $response;
    }
}
