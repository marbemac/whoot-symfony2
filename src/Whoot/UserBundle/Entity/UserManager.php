<?php

namespace Whoot\UserBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\UserFollowing;
use Whoot\WhootBundle\Entity\LocationManager;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Entity\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class UserManager extends BaseUserManager
{
    protected $em;
    protected $locationManager;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EncoderFactoryInterface $encoderFactory, $algorithm, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, EntityManager $em, $class, LocationManager $locationManager)
    {
        parent::__construct($encoderFactory, $algorithm, $usernameCanonicalizer, $emailCanonicalizer, $em, $class);
        $this->em = $em;
        $this->locationManager = $locationManager;
    }

    public function createUser()
    {
        $user = new User();
        $user->setAlgorithm($this->algorithm);

        return $user;
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
            $fromUser = $this->em->getRepository('WhootUserBundle:User')->find($fromUser);
            $toUser = $this->em->getRepository('WhootUserBundle:User')->find($toUser);

            $response['status'] = 'new';

            $connection = new UserFollowing();
            $connection->setUser($fromUser);
            $connection->setFollowing($toUser);
            $this->em->persist($connection);
        }

        $this->em->flush();

        return $response;
    }

    public function getFollowing($user, $offset, $limit)
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

        if ($limit && $offset != null)
        {
            $qb->setFirstResult($offset)
               ->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $followingUsers = $query->getArrayResult();

        return $followingUsers;
    }

    public function getFollowers($user, $offset, $limit)
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
            $followingUsers = $this->getFollowing($user, null, null);

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
