<?php

namespace Whoot\UserBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Document\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;

class UserManager extends BaseUserManager
{
    public function __construct(EncoderFactoryInterface $encoderFactory, $algorithm, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, DocumentManager $dm, $class)
    {
        parent::__construct($encoderFactory, $algorithm, $usernameCanonicalizer, $emailCanonicalizer, $dm, $class);
    }

    public function findUserBy(array $criteria)
    {
        $qb = $this->dm->createQueryBuilder($this->class);

        foreach ($criteria as $field => $val)
        {
            $qb->field($field)->equals($val);
        }

        $query = $qb->getQuery();

        return $query->getSingleResult();
    }

    public function findUsersBy(array $criteria, array $inCriteria = array(), $limit = null, $offset = 0)
    {
        $qb = $this->dm->createQueryBuilder($this->class);

        foreach ($criteria as $field => $val)
        {
            $qb->field($field)->equals($val);
        }

        foreach ($inCriteria as $field => $vals)
        {
            $vals = is_array($vals) ? $vals : array();
            $qb->field($field)->in($vals);
        }

        if ($limit !== null && $offset !== null)
        {
            $qb->limit($limit)
               ->skip($offset);
        }
                
        $query = $qb->getQuery();

        return $query->execute();
    }

    /*
     * Get users for a search query. Return followed users first.
     *
     * @param integer $userId
     * @param string $query
     */
    public function findForSearch($userId, $search)
    {
        $slug = new SlugNormalizer($search);
        $qb = $this->dm->createQueryBuilder($this->class);

        $qb->field('status')->equals('Active')
            ->field('nameSlug')->equals(new \MongoRegex("/^{$slug->__toString()}/"));
//           ->where("function() { return this.nameSlug.match('".$slug->__toString()."'); }");

        $query = $qb->getQuery();
        $users = $query->execute();

        $response = array();
        foreach ($users as $user)
        {
            $response[] = array(
                'name' => $user->getFullName(),
                'username' => $user->getUsername()
            );
        }
        return $response;
    }

    public function toggleFollow($fromUser, $toUserId)
    {
        $response = array();
        $key = array_search($toUserId, $fromUser->getFollowing());

        if ($key !== false)
        {
            $response['status'] = 'removed';
            $fromUser->removeFollowing($key);
        }
        else
        {
            $response['status'] = 'new';
            $fromUser->addFollowing($toUserId);
        }

        $this->updateUser($fromUser);

        return $response;
    }

    public function addPing($fromUser, $toUserId, $andFlush = true)
    {
        $fromUser->addPing($toUserId);

        $this->updateUser($fromUser, $andFlush);
    }

    public function findUndecided($user, $since, $offset, $limit)
    {
        $qb = $this->dm->createQueryBuilder($this->class);
        $qb->field('status')->equals('Active')
            ->field('id')->in($user->getFollowing())
            ->addOr($qb->expr()->field('currentPost.createdAt')->lte(new \MongoDate(strtotime($since))))
            ->addOr($qb->expr()->field('currentPost.createdAt')->exists(false));

        $query = $qb->getQuery();

        if ($limit !== null && $offset !== null)
        {
            $qb->limit($limit)
               ->skip($offset);
        }

        return $query->execute();
    }

    // OLD CODE BELOW -----------------------

//    public function getUser(array $criteria, $returnObject = true)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('u', 'l'))
//           ->from('Whoot\UserBundle\Entity\User', 'u')
//           ->leftJoin('u.location', 'l');
//
//        foreach ($criteria as $key => $val)
//        {
//            $qb->andWhere('u.'.$key.' = :'.$key);
//            $qb->setParameter($key, $val);
//        }
//
//        $query = $qb->getQuery();
//        try {
//            $user = $query->getSingleResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
//        } catch (\Doctrine\Orm\NoResultException $e) {
//            $user = null;
//        }
//
//        return $user;
//    }
//
//    public function getUsersBy($currentUser)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('u'))
//           ->from('Whoot\UserBundle\Entity\User', 'u')
//           ->where('u.status = :status AND u.id != :currentUser')
//           ->setParameters(array(
//               'status' => 'Active',
//               'currentUser' => $currentUser
//           ));
//
//        $query = $qb->getQuery();
//        $users = $query->getArrayResult();
//
//        return $users;
//    }
//
//    /*
//     * Check for a follow connection between two users. Return if found.
//     */
//    public function findFollowConnections(array $criteria, $returnObject = false)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('f'))
//           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'f');
//
//        foreach ($criteria as $key => $val)
//        {
//            $qb->andWhere('f.'.$key.' = :'.$key);
//        }
//        $qb->setParameters($criteria);
//
//        $query = $qb->getQuery();
//        $connections = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
//
//        return $connections;
//    }
//
//    public function toggleFollow($fromUser, $toUser)
//    {
//        $connection = $this->findFollowConnections(array('user' => $fromUser, 'following' => $toUser), true);
//        $connection = isset($connection[0]) ? $connection[0] : null;
//
//        $response = array();
//
//        if ($connection)
//        {
//            $response['status'] = 'existing';
//            $this->notificationManager->removeNotification(array('affectedUser' => $toUser, 'type' => 'Follow'), $connection->getCreatedAt(), false);
//            $this->em->remove($connection);
//        }
//        else
//        {
//            $fromUser = $this->em->getRepository('WhootUserBundle:User')->find($fromUser);
//            $toUser = $this->em->getRepository('WhootUserBundle:User')->find($toUser);
//
//            $response['status'] = 'new';
//
//            $connection = new UserFollowing();
//            $connection->setUser($fromUser);
//            $connection->setFollowing($toUser);
//            $this->em->persist($connection);
//
//            $this->notificationManager->addNotification('Follow', $toUser, null, null, false);
//        }
//
//        $this->em->flush();
//
//        return $response;
//    }
//
//    public function getFollowing($user, $dateRange, $offset, $limit)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('u'))
//           ->from('Whoot\UserBundle\Entity\User', 'u')
//           ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
//           ->where('u.status = :status')
//           ->setParameters(array(
//               'user' => $user,
//               'status' => 'Active'
//           ));
//
//        if ($dateRange)
//        {
//            $qb->andWhere('f.createdAt >= :dateFrom')
//               ->andWhere('f.createdAt <= :dateTo')
//               ->setParameters(array(
//                                   'dateFrom' => $dateRange['from'],
//                                   'dateTo' => $dateRange['to']
//                               ));
//        }
//
//        if ($limit && $offset != null)
//        {
//            $qb->setFirstResult($offset)
//               ->setMaxResults($limit);
//        }
//
//        $query = $qb->getQuery();
//        $followingUsers = $query->getArrayResult();
//
//        return $followingUsers;
//    }
//
//    public function getFollowers($user, $dateRange, $offset, $limit)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('u'))
//           ->from('Whoot\UserBundle\Entity\User', 'u')
//           ->innerJoin('u.following', 'f', 'WITH', 'f.following = :user AND f.status = :status')
//           ->where('u.status = :status')
//           ->setParameters(array(
//               'user' => $user,
//               'status' => 'Active'
//           ));
//
//        if ($dateRange)
//        {
//            $qb->andWhere('f.createdAt >= :dateFrom')
//               ->andWhere('f.createdAt <= :dateTo')
//               ->setParameters(array(
//                                   'dateFrom' => $dateRange['from'],
//                                   'dateTo' => $dateRange['to']
//                               ));
//        }
//
//        if ($limit && $offset != null)
//        {
//            $qb->setFirstResult($offset)
//               ->setMaxResults($limit);
//        }
//
//        $query = $qb->getQuery();
//        $followersUsers = $query->getArrayResult();
//
//        return $followersUsers;
//    }
//
//    public function findUndecided($user, $since, $listId, $offset, $limit)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('u'))
//           ->from('Whoot\UserBundle\Entity\User', 'u')
//           ->leftJoin('u.posts', 'up', 'WITH', 'up.createdAt >= :since AND up.status = :status')
//           ->having('count(up.id) = 0')
//           ->groupBy('u.id')
//           ->orderBy('u.firstName', 'ASC')
//           ->addOrderBy('u.lastName', 'ASC')
//           ->setParameters(array(
//               'since' => $since,
//               'status' => 'Active'
//           ));
//
//        if ($listId)
//        {
//            // get the users in the list
//            $qb2 = $this->em->createQueryBuilder();
//            $qb2->select(array('ul', 'u'))
//               ->from('Whoot\WhootBundle\Entity\UserLList', 'ul')
//               ->innerJoin('ul.user', 'u', 'WITH', 'u.status = :status')
//               ->where('ul.list = :listId')
//               ->andWhere('ul.status = :status')
//               ->setParameters(array(
//                   'status' => 'Active',
//                   'listId' => $listId
//               ));
//            $query2 = $qb2->getQuery();
//            $listUsers = $query2->getArrayResult();
//            $users = array();
//            foreach ($listUsers as $listUser)
//            {
//                $users[] = $listUser['user']['id'];
//            }
//
//            if (count($users) == 0)
//            {
//                return array();
//            }
//
//            $qb->andwhere($qb->expr()->in('u.id', $users));
//        }
//        else if ($user)
//        {
//            $followingUsers = $this->getFollowing($user, null, null, null);
//
//            // If they are not following anyone, there will be no undecided...
//            if (count($followingUsers) == 0)
//                return array();
//
//            $following = array();
//            foreach ($followingUsers as $followingUser)
//            {
//                $following[] = $followingUser['id'];
//            }
//
//            $qb->andwhere($qb->expr()->in('u.id', $following));
//        }
//
//        if ($limit && $offset != null)
//        {
//            $qb->setFirstResult($offset)
//               ->setMaxResults($limit);
//        }
//
//        $query = $qb->getQuery();
//        $users = $query->getArrayResult();
//
//        return $users;
//    }
//
//    /**
//     * Finds a user by username or email
//     *
//     * @param string $usernameOrEmail
//     * @return UserInterface
//     */
//    public function findUserByUsername($usernameOrEmail)
//    {
//        if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
//            return $this->findUserByEmail($usernameOrEmail);
//        }
//
//        return $this->findUserBy(array('usernameCanonical' => $this->canonicalizeUsername($usernameOrEmail)));
//    }
//
//    /**
//     * Get the # of followers and following for a user.
//     *
//     * @param  integer $userId
//     * @return array $followingStats
//     */
//    public function getFollowingStats($userId)
//    {
//        $response = array();
//
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('count(uf.id) AS cnt'))
//           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'uf')
//           ->where('uf.status = :status AND uf.user = :userId')
//           ->setParameters(array(
//               'userId' => $userId,
//               'status' => 'Active'
//           ));
//
//        $query = $qb->getQuery();
//        $response['following'] = $query->getArrayResult();
//
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('count(uf.id) AS cnt'))
//           ->from('Whoot\WhootBundle\Entity\UserFollowing', 'uf')
//           ->where('uf.status = :status AND uf.following = :userId')
//           ->setParameters(array(
//               'userId' => $userId,
//               'status' => 'Active'
//           ));
//
//        $query = $qb->getQuery();
//        $response['followers'] = $query->getArrayResult();
//
//        return $response;
//    }
//
//
//
//    public function getUserLocation($userId=null)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('l'))
//           ->from('Whoot\WhootBundle\Entity\Location', 'l')
//           ->innerJoin('l.users', 'u', 'WITH', 'u.id = :userId')
//           ->setParameters(array(
//               'userId' => $userId
//           ));
//
//        $query = $qb->getQuery();
//        $query->useResultCache(true, 300, 'user_location_'.$userId);
//        $results = $query->getArrayResult();
//
//        return isset($results[0]) ? $results[0] : null;
//    }
}
