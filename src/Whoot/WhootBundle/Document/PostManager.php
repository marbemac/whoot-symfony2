<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;
use Whoot\WhootBundle\Util\DateConverter;

class PostManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createPost()
    {
        return $this->createObject();
    }

    public function deletePost(Post $post, $andFlush = true)
    {
        return $this->deleteObject($post, $andFlush);
    }

    public function updatePost(Post $post, $andFlush = true)
    {
        return $this->updateObject($post, $andFlush);
    }

    public function findPostBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findPostsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function getMyPost($user)
    {
        if ($user->getCurrentPost())
        {
            if ($user->getCurrentPost()->isValid())
            {
                return $this->findPostBy(array('id' => $user->getCurrentPost()->getPost()));
            }
        }

        return null;
    }

    public function disableDailyPosts($user)
    {
        $start = new DateConverter(null, 'Y-m-d', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
        $oldPosts = $this->findPostsBy(array('createdBy' => $user->getId(), 'isCurrentPost' => true), array(), array(), array('target' => 'createdAt', 'start' => $start));
        foreach ($oldPosts as $oldPost)
        {
            $oldPost->setIsCurrentPost(false);
            $this->updatePost($oldPost, false);

            if ($oldPost->getInvite())
            {
                $this->m->Invite->update(
                    array('_id' => $oldPost->getInvite()->getInvite()),
                    array(
                        '$inc' =>
                            array(
                                'attendingCount' => -1
                            ),
                        '$unset' =>
                            array(
                                'attending.'.$user->getId()->__toString() => 1,
                            )
                    )
                );
            }
        }
        $this->dm->flush();
    }

    public function setInvitePost($invite, $user)
    {
        $post = $this->findPostBy(array('createdBy' => $user->getId(), 'invite.invite' => $invite->getId()));

        if ($post)
        {
            $post->setIsCurrentPost(true);
        }
        else
        {
            $post = $this->createPost();
            $post->setInvite($invite);
            $post->setCreatedBy($user->getId()->__toString());
        }

        $this->updatePost($post);
        $user->setCurrentPost($post);
        $this->dm->persist($user);
        $this->dm->flush();
    }

    public function activateLastPost($user)
    {
        $qb = $this->dm->createQueryBuilder($this->class);

        $start = new DateConverter(null, 'Y-m-d', '-5 hours', $user->getCurrentLocation() ? $user->getCurrentLocation()->getTimezone() : 'UTC');
        $qb->field('createdBy')->equals($user->getId())
            ->field('isCurrentPost')->equals(false)
            ->field('invite')->exists(false)
            ->field('createdAt')->gte(new \MongoDate(strtotime($start->__toString())))
            ->sort('createdAt', 'DESC');

        $query = $qb->getQuery();

        $post = $query->getSingleResult();

        if ($post)
        {
            $post->setIsCurrentPost(true);
            $user->setCurrentPost($post);
            $this->updatePost($post);
        }
        else
        {
            $user->setCurrentPost(null);
        }

        $this->dm->persist($user);
    }

//    public function findPostBy($postId, $createdBy=null, $createdAt=null, $postStatus=null, $returnObject=false)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('p', 'cb', 'pw', 'w', 'l', 'v', 'vcb', 'i', 'icb'))
//           ->from('Whoot\WhootBundle\Entity\Post', 'p')
//           ->innerJoin('p.createdBy', 'cb')
//           ->leftJoin('p.words', 'pw')
//           ->leftJoin('pw.word', 'w')
//           ->leftJoin('p.location', 'l')
//           ->leftJoin('p.votes', 'v')
//           ->leftJoin('v.voter', 'vcb')
//           ->leftJoin('p.invite', 'i')
//           ->leftJoin('i.createdBy', 'icb');
//
//        if ($postId)
//        {
//            $qb->andWhere('p.id = :postId');
//            $qb->setParameter('postId', $postId);
//        }
//
//        if ($postStatus)
//        {
//            $qb->andWhere('p.status = :postStatus');
//            $qb->setParameter('postStatus', $postStatus);
//        }
//
//        if ($createdAt)
//        {
//            $qb->andWhere('p.createdAt >= :createdAt');
//            $qb->setParameter('createdAt', $createdAt);
//        }
//
//        if ($createdBy)
//        {
//            $qb->andWhere('p.createdBy = :createdBy')
//               ->setParameter('createdBy', $createdBy);
//        }
//
//        $query = $qb->getQuery();
//        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
//        $post = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
//
//        return isset($post[0]) ? $post[0] : null;
//    }
//
//    /*
//     * The main way to get feeds. Returns an array of posts based on specified conditions.
//     *
//     * @param array $user Get objects of users this user is following.
//     * @param array $postTypes Array of strings of posts types to include. Topic|Talk|News|Question|Procon|List|Video|Picture.
//     * @param string $sortBy How do we want to sort the list? Popular|Newest|Controversial|Upcoming
//     * @param date $createdAt
//     * @param integer $listId Are we pulling from list users?
//     * @param integer $offset
//     * @param integer $limit
//     * @param bool $returnObject
//     *
//     * @return array $posts
//     */
//    public function findPostsBy($user, $postTypes, $sortBy, $createdAt, $listId, $offset, $limit, $returnObject=false)
//    {
//        $qb = $this->em->createQueryBuilder();
//        $qb->select(array('p', 'cb'))
//           ->from('Whoot\WhootBundle\Entity\Post', 'p')
//           ->innerJoin('p.createdBy', 'cb')
////           ->innerJoin('p.users', 'pu', 'WITH', 'pu.status = :status')
//           ->where('p.status = :status')
//           ->groupBy('p.id')
//           ->setParameters(array(
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
//               ->setParameters(array(
//                   'status' => 'Active',
//                   'listId' => $listId
//               ));
//            $query2 = $qb2->getQuery();
//            $listUsers = $query2->getArrayResult();
//            $users = array();
//
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
//            $qb->andwhere($qb->expr()->in('cb.id', $users));
//        }
//        else if ($user)
//        {
//            // get the users this user is following
//            $qb2 = $this->em->createQueryBuilder();
//            $qb2->select(array('u.id'))
//               ->from('Whoot\UserBundle\Entity\User', 'u')
//               ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
//               ->setParameters(array(
//                   'user' => $user,
//                   'status' => 'Active'
//               ));
//            $query2 = $qb2->getQuery();
//            $followingUsers = $query2->getArrayResult();
//            $following = array($user->getId());
//            foreach ($followingUsers as $followingUser)
//            {
//                $following[] = $followingUser['id'];
//            }
//
//            $qb->andwhere($qb->expr()->in('cb.id', $following));
//        }
//
//        switch ($sortBy)
//        {
//            case 'popularity':
//                $qb->orderBy('p.score', 'DESC');
//                $qb->addOrderBy('p.createdAt', 'DESC');
//                break;
//            default:
//                $qb->orderBy('p.createdAt', 'DESC');
//        }
//
//        if ($createdAt)
//        {
//            $qb->andWhere('p.createdAt >= :createdAt');
//            $qb->setParameter('createdAt', $createdAt);
//        }
//
//        if ($postTypes)
//        {
//            $qb->andwhere($qb->expr()->in('p.type', $postTypes));
//        }
//
//        if ($offset != null && $limit)
//        {
//            $qb->setFirstResult($offset)
//               ->setMaxResults($limit);
//        }
//
//        $query = $qb->getQuery();
//        $objects = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
//
//        return $objects;
//    }
//
//    /*
//     * Disables all posts for the given user for today.
//     */
//    public function disableDailyPosts($user)
//    {
//        $post = $this->findPostBy(null, $user, date('Y-m-d 05:00:00', time()-(60*60*5)), 'Active', true);
//
//        if ($post)
//        {
//            $this->deletePost($post);
//        }
//    }
}
