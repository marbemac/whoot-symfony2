<?php

namespace Whoot\UserBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\UserFollowing;
use Whoot\WhootBundle\Entity\LocationManager;

use Whoot\NotificationBundle\Entity\NotificationManager;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserManager extends BaseUserManager
{
    protected $em;
    protected $locationManager;
    protected $notificationManager;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, $algorithm, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, EntityManager $em, $class, LocationManager $locationManager, NotificationManager $notificationManager)
    {
        parent::__construct($encoderFactory, $algorithm, $usernameCanonicalizer, $emailCanonicalizer, $em, $class);
        $this->em = $em;
        $this->locationManager = $locationManager;
        $this->notificationManager = $notificationManager;
    }

    public function getUser(array $criteria, $returnObject = true)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u', 'l'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
           ->leftJoin('u.location', 'l');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('u.'.$key.' = :'.$key);
            $qb->setParameter($key, $val);
        }

        $query = $qb->getQuery();
        try {
            $user = $query->getSingleResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
        } catch (\Doctrine\Orm\NoResultException $e) {
            $user = null;
        }

        return $user;
    }

    public function getUsersBy($currentUser)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
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
     */
    public function findFollowConnections(array $criteria, $returnObject = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('f'))
           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'f');

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('f.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);

        $query = $qb->getQuery();
        $connections = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return $connections;
    }

    public function toggleFollow($fromUser, $toUser)
    {
        $connection = $this->findFollowConnections(array('user' => $fromUser, 'following' => $toUser), true);
        $connection = isset($connection[0]) ? $connection[0] : null;

        $response = array();

        if ($connection)
        {
            $response['status'] = 'existing';
            $this->notificationManager->removeNotification(array('affectedUser' => $toUser, 'type' => 'Follow'), $connection->getCreatedAt(), false);
            $this->em->remove($connection);
        }
        else
        {
            $fromUser = $this->em->getRepository('WhootUserBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('WhootUserBundle:User')->find($toUser);

            $response['status'] = 'new';

            $connection = new UserFollowing();
            $connection->setUser($fromUser);
            $connection->setFollowing($toUser);
            $this->em->persist($connection);
            
            $this->notificationManager->addNotification('Follow', $toUser, null, null, false);
        }

        $this->em->flush();

        return $response;
    }

    public function getFollowing($user, $dateRange, $offset, $limit)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
           ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
           ->where('u.status = :status')
           ->setParameters(array(
               'user' => $user,
               'status' => 'Active'
           ));

        if ($dateRange)
        {
            $qb->andWhere('f.createdAt >= :dateFrom')
               ->andWhere('f.createdAt <= :dateTo')
               ->setParameters(array(
                                   'dateFrom' => $dateRange['from'],
                                   'dateTo' => $dateRange['to']
                               ));
        }

        if ($limit && $offset != null)
        {
            $qb->setFirstResult($offset)
               ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $followingUsers = $query->getArrayResult();

        return $followingUsers;
    }

    public function getFollowers($user, $dateRange, $offset, $limit)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
           ->innerJoin('u.following', 'f', 'WITH', 'f.following = :user AND f.status = :status')
           ->where('u.status = :status')
           ->setParameters(array(
               'user' => $user,
               'status' => 'Active'
           ));

        if ($dateRange)
        {
            $qb->andWhere('f.createdAt >= :dateFrom')
               ->andWhere('f.createdAt <= :dateTo')
               ->setParameters(array(
                                   'dateFrom' => $dateRange['from'],
                                   'dateTo' => $dateRange['to']
                               ));
        }

        if ($limit && $offset != null)
        {
            $qb->setFirstResult($offset)
               ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $followersUsers = $query->getArrayResult();

        return $followersUsers;
    }

    public function findUndecided($user, $since, $listId, $offset, $limit)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
           ->leftJoin('u.posts', 'up', 'WITH', 'up.createdAt >= :since AND up.status = :status')
           ->having('count(up.id) = 0')
           ->groupBy('u.id')
           ->orderBy('u.firstName', 'ASC')
           ->addOrderBy('u.lastName', 'ASC')
           ->setParameters(array(
               'since' => $since,
               'status' => 'Active'
           ));

        if ($listId)
        {
            // get the users in the list
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select(array('ul', 'u'))
               ->from('Whoot\WhootBundle\Entity\UserLList', 'ul')
               ->innerJoin('ul.user', 'u', 'WITH', 'u.status = :status')
               ->where('ul.list = :listId')
               ->andWhere('ul.status = :status')
               ->setParameters(array(
                   'status' => 'Active',
                   'listId' => $listId
               ));
            $query2 = $qb2->getQuery();
            $listUsers = $query2->getArrayResult();
            $users = array();
            foreach ($listUsers as $listUser)
            {
                $users[] = $listUser['user']['id'];
            }

            if (count($users) == 0)
            {
                return array();
            }

            $qb->andwhere($qb->expr()->in('u.id', $users));
        }
        else if ($user)
        {
            $followingUsers = $this->getFollowing($user, null, null, null);

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

        if ($limit && $offset != null)
        {
            $qb->setFirstResult($offset)
               ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $users = $query->getArrayResult();

        return $users;
    }

    /**
     * Finds a user by username or email
     *
     * @param string $usernameOrEmail
     * @return UserInterface
     */
    public function findUserByUsername($usernameOrEmail)
    {
        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
            return $this->findUserByEmail($usernameOrEmail);
        }

        return $this->findUserBy(array('usernameCanonical' => $this->canonicalizeUsername($usernameOrEmail)));
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

    /*
     * Get users for a search query. Return followed users first.
     *
     * @param integer $userId
     * @param string $query
     */
    public function findForSearch($userId, $search)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('u', 'p', 'l'))
           ->from('Whoot\UserBundle\Entity\User', 'u')
           ->leftJoin('u.posts', 'p', 'WITH', 'p.status = :status AND p.createdAt >= :createdAt')
           ->leftJoin('u.location', 'l')
           ->where(
               $qb->expr()->like('CONCAT(u.firstName, u.lastName)', ':query')
           )
           ->setParameters(array(
               'query' => '%'.$search.'%',
               'status' => 'Active',
                'createdAt'    => date('Y-m-d 05:00:00', time()-(60*60*5))
           ));

        $query = $qb->getQuery();
        $query->useResultCache(true, 300, 'user_search_'.$search);
        $results = $query->getArrayResult();

        $response = array();
        foreach ($results as $result)
        {
            $response[] = array(
                'name' => $result['firstName'].' '.$result['lastName'],
                'username' => $result['username'],
                'id' => $result['id'],
                'location' => isset($result['location']['cityName']) ? $result['location']['cityName'].', '.$result['location']['stateName'] : 'Outer Space',
                'profileImage' => $result['profileImage'],
                'postId' => isset($result['post'][0]) ? $result['post'][0]['id'] : null
            );
        }

        return $response;
    }

    public function getUserLocation($userId=null)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('l'))
           ->from('Whoot\WhootBundle\Entity\Location', 'l')
           ->innerJoin('l.users', 'u', 'WITH', 'u.id = :userId')
           ->setParameters(array(
               'userId' => $userId
           ));

        $query = $qb->getQuery();
        $query->useResultCache(true, 300, 'user_location_'.$userId);
        $results = $query->getArrayResult();

        return isset($results[0]) ? $results[0] : null;
    }
}
