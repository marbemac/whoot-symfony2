<?php

namespace Whoot\UserBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;

use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

use FOS\UserBundle\Model\UserInterface;
use FOS\UserBundle\Document\UserManager as BaseUserManager;
use FOS\UserBundle\Util\CanonicalizerInterface;
use Whoot\WhootBundle\Util\SlugNormalizer;
use Whoot\WhootBundle\Util\DateConverter;

class UserManager extends BaseUserManager
{
    protected $m;
    
    public function __construct(EncoderFactoryInterface $encoderFactory, $algorithm, CanonicalizerInterface $usernameCanonicalizer, CanonicalizerInterface $emailCanonicalizer, DocumentManager $dm, $class)
    {
        parent::__construct($encoderFactory, $algorithm, $usernameCanonicalizer, $emailCanonicalizer, $dm, $class);

        $this->m = $dm->getConnection()->selectDatabase($dm->getConfiguration()->getDefaultDB());
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
    public function findForSearch($user, $search, $onlyFollowing, $limit)
    {
        $slug = new SlugNormalizer($search);
        $qb = $this->dm->createQueryBuilder($this->class);

        $followingIds = $user->getFollowing();

        $qb->field('status')->equals('Active')
            ->select('id', 'firstName', 'lastName', 'username', 'currentProfileImage', 'currentLocation')
            ->field('id')->in($followingIds)
            ->field('nameSlug')->equals(new \MongoRegex("/^{$slug->__toString()}/"))
            ->field('blocked_by')->notEqual($user->getId())
            ->limit($limit);

        $query = $qb->getQuery();
        $followingFound = $query->execute();

        $foundUsers = array();
        foreach ($followingFound as $foundUser)
        {
            $foundUsers[] = $foundUser;
        }

        // Are we also getting users we are not following?
        if (!$onlyFollowing && count($foundUsers) < $limit)
        {
            $qb = $this->dm->createQueryBuilder($this->class);
            $qb->field('status')->equals('Active')
                ->select('id', 'firstName', 'lastName', 'username', 'currentProfileImage', 'currentLocation')
                ->field('nameSlug')->equals(new \MongoRegex("/^{$slug->__toString()}/"))
                ->field('blocked_by')->notEqual($user->getId())
                ->limit($limit - count($followingFound));

            if (count($foundUsers) > 0)
            {
                $qb->field('id')->notIn($followingIds);
            }

            $query = $qb->getQuery();
            $users = $query->execute();

            foreach ($users as $foundUser)
            {
                $foundUsers[] = $foundUser;
            }
        }

        $response = array();
        foreach ($foundUsers as $foundUser)
        {
            $response[] = array(
                'id' => $foundUser->getId()->__toString(),
                'name' => $foundUser->getFullName(),
                'username' => $foundUser->getUsername(),
                'profileImage' => $foundUser->getCurrentProfileImage(),
                'location' => $foundUser->getCurrentLocation() ? $user->getCurrentLocation()->getName() : null
            );
        }
        return $response;
    }

    public function toggleFollow($fromUser, $toUserId, $singleDirection=null)
    {
        $response = array();
        $toUser = $this->findUserBy(array('id' => new \MongoId($toUserId)));

        $isFollowing = $this->m->User->findOne(
                                array(
                                    '_id' => $fromUser->getId(),
                                    'following.'.$toUser->getId()->__toString() => $toUser->getId()
                                )
                            );

        // Remove the follower
        if ($singleDirection != 'add' && $toUser && $isFollowing)
        {
            $response['status'] = 'removed';
            $this->m->User->update(
                array('_id' => $fromUser->getId()),
                array(
                    '$inc' =>
                        array(
                            'followingCount' => -1
                        ),
                    '$unset' =>
                        array(
                            'following.'.$toUser->getId()->__toString() => 1,
                        )
                )
            );
            $this->m->User->update(
                array('_id' => $toUser->getId()),
                array(
                    '$inc' =>
                        array(
                            'followerCount' => -1
                        )
                )
            );
        }
        // Add the follower
        else if ($singleDirection != 'remove' && $toUser && !$isFollowing)
        {
            $response['status'] = 'new';
            $this->m->User->update(
                array('_id' => $fromUser->getId()),
                array(
                    '$inc' =>
                        array(
                            'followingCount' => 1
                        ),
                    '$set' =>
                        array(
                            'following.'.$toUser->getId()->__toString() => $toUser->getId(),
                        )
                )
            );
            $this->m->User->update(
                array('_id' => $toUser->getId()),
                array(
                    '$inc' =>
                        array(
                            'followerCount' => 1
                        )
                )
            );
        }

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
     * Blocks $blockUserId from viewing $user
     *
     * @param $user
     * @param $blockUserId
     *
     * @return void
     */
    public function blockUser($user, $blockUserId)
    {
        $blockUser = $this->findUserBy(array('id' => new \MongoId($blockUserId)));
        if ($blockUser)
        {
            $blockUser->addBlockedBy($user->getId());
            $this->updateUser($blockUser, true);
        }

        return;
    }

    /**
     * Unblocks $blockUserId from viewing $user
     *
     * @param $user
     * @param $blockUserId
     *
     * @return void
     */
    public function unblockUser($user, $blockUserId)
    {
        $blockUser = $this->findUserBy(array('id' => new \MongoId($blockUserId)));
        if ($blockUser)
        {
            $blockUser->removeBlockedBy($user->getId());
            $this->updateUser($blockUser, true);
        }

        return;
    }

    function updateFollowingCounts($user, $andFlush = true)
    {
        $followerCount = $this->m->User->count(
                array(
                    'following.'.$user->getId()->__toString() => array('$exists' => true),
                    'blocked_by' => array('$ne' => $user->getId())
                )
            );
        $qb = $this->dm->createQueryBuilder($this->class);
        $qb->select('id')
           ->field('blocked_by')->equals($user->getId());
        $query = $qb->getQuery();
        $blocked_users = $query->execute();
        $blocked_ids = array();
        foreach ($blocked_users as $blocked_user)
        {
            $blocked_ids[] = $blocked_user->getId()->__toString();
        }

        $user->setFollowingCount($user->followCountBlockAdjust($blocked_ids));
        $user->setFollowerCount($followerCount);
        
        $this->updateUser($user, true);
    }
}
