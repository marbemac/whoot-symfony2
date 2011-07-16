<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootUserBundle\Entity\UserManager;


class PostManager
{
    protected $userManager;
    protected $em;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct($userManager, EntityManager $em)
    {
        $this->userManager = $userManager;
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function createPost()
    {
        $post = new Post();
        return $post;
    }

    /**
     * {@inheritDoc}
     */
    public function deletePost(Post $post, $andFlush=true)
    {
        $post->setStatus('Deleted');
        $this->em->persist($post);

        if ($andFlush)
        {
            $this->em->flush();
        }
        
        return array('result' => 'success');
    }

    public function updatePost(Post $post, $andFlush = true)
    {
        $this->em->persist($post);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    public function findPostBy($postId, $createdBy=null, $createdAt=null, $postStatus=null, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'cb', 'pw', 'w', 'l', 'v', 'vcb'))
           ->from('Whoot\WhootBundle\Entity\Post', 'p')
           ->innerJoin('p.createdBy', 'cb')
           ->leftJoin('p.words', 'pw')
           ->leftJoin('pw.word', 'w')
           ->leftJoin('p.location', 'l')
           ->leftJoin('p.votes', 'v')
           ->leftJoin('v.voter', 'vcb');

        if ($postId)
        {
            $qb->andWhere('p.id = :postId');
            $qb->setParameter('postId', $postId);
        }

        if ($postStatus)
        {
            $qb->andWhere('p.status = :postStatus');
            $qb->setParameter('postStatus', $postStatus);
        }

        if ($createdAt)
        {
            $qb->andWhere('p.createdAt >= :createdAt');
            $qb->setParameter('createdAt', $createdAt);
        }

        if ($createdBy)
        {
            $qb->andWhere('p.createdBy = :createdBy')
               ->setParameter('createdBy', $createdBy);
        }

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $post = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($post[0]) ? $post[0] : null;
    }

    /*
     * The main way to get feeds. Returns an array of posts based on specified conditions.
     *
     * @param array $user Get objects of users this user is following.
     * @param array $postTypes Array of strings of posts types to include. Topic|Talk|News|Question|Procon|List|Video|Picture.
     * @param string $sortBy How do we want to sort the list? Popular|Newest|Controversial|Upcoming
     * @param date $createdAt
     * @param integer $listId Are we pulling from list users?
     * @param integer $offset
     * @param integer $limit
     * @param bool $returnObject
     *
     * @return array $posts
     */
    public function findPostsBy($user, $postTypes, $sortBy, $createdAt, $listId, $offset, $limit, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'cb'))
           ->from('Whoot\WhootBundle\Entity\Post', 'p')
           ->innerJoin('p.createdBy', 'cb')
//           ->innerJoin('p.users', 'pu', 'WITH', 'pu.status = :status')
           ->where('p.status = :status')
           ->groupBy('p.id')
           ->setParameters(array(
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

            $qb->andwhere($qb->expr()->in('cb.id', $users));
        }
        else if ($user)
        {
            // get the users this user is following
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select(array('u.id'))
               ->from('Whoot\WhootUserBundle\Entity\User', 'u')
               ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
               ->setParameters(array(
                   'user' => $user,
                   'status' => 'Active'
               ));
            $query2 = $qb2->getQuery();
            $followingUsers = $query2->getArrayResult();
            $following = array($user->getId());
            foreach ($followingUsers as $followingUser)
            {
                $following[] = $followingUser['id'];
            }

            $qb->andwhere($qb->expr()->in('cb.id', $following));
        }

        switch ($sortBy)
        {
            case 'popularity':
                $qb->orderBy('p.score', 'DESC');
                $qb->addOrderBy('p.createdAt', 'DESC');
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }

        if ($createdAt)
        {
            $qb->andWhere('p.createdAt >= :createdAt');
            $qb->setParameter('createdAt', $createdAt);
        }

        if ($postTypes)
        {
            $qb->andwhere($qb->expr()->in('p.type', $postTypes));
        }

        if ($offset)
        {
            $qb->setFirstResult($offset);
        }
        
        if ($limit)
        {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $objects = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return $objects;
    }

    /*
     * Creates a post for a user if the post does not yet exist today.
     * Updates the post if the user already has a post for the day.
     * 
     * @param array $data
     * @param User $user
     * 
     * @return array $result
     */
//    public function togglePost($data, $user)
//    {
//        $result = array('status' => 'existing');
//        $userPost = $this->findMyPost($user, 'Active', true);
//        if ($userPost)
//        {
//            $userPost->setStatus('Disabled');
//
//            // If we have a previous post, and we are the only one connected to it, disable it.
//            if (count($userPost->getPost()->getUsers()) == 1)
//            {
//                $userPost->getPost()->setStatus('Disabled');
//                $this->updatePost($userPost->getPost(), false);
//            }
//
//            $this->em->persist($userPost);
//        }
//
//        $post = $this->createPost();
//        $post->setType($data['type']);
//        $post->setNote($data['note']);
//        if (isset($data['address']) && $data['address'])
//        {
//            $post->setVenue($data['venue']);
//            $post->setAddress($data['address']);
//            $post->setLat($data['address_lat']);
//            $post->setLon($data['address_lon']);
//            $post->setTime($data['time']);
//            $post->setIsOpenInvite(true);
//        }
//        $post->setCreatedBy($user);
//        $this->updatePost($post, false);
//
//        $newUserPost = new UsersPosts();
//        $newUserPost->setPost($post);
//        $newUserPost->setUser($user);
//        $this->em->persist($newUserPost);
//
//        $this->em->flush();
//
//        $result['status'] = 'new';
//        $result['post'] = $newUserPost;
//
//        return $result;
//    }

    /**
     * Given a post data structure, extract and sort it's activity.
     *
     * @param Post $post Must include the post.createdBy, post.users, and post.users.user
     *
     * @return array $activity
     */
    public function buildActivity($post)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('c', 'cb'))
           ->from('Whoot\WhootBundle\Entity\Comment', 'c')
           ->innerJoin('c.createdBy', 'cb')
           ->where('c.post = :post AND c.status = :status')
           ->setParameters(array(
               'post'    => $post['id'],
               'status'       => 'Active'
           ));

        $query = $qb->getQuery();
        $comments = $query->getResult(Query::HYDRATE_ARRAY);

        $activity = array();
//        foreach ($post['users'] as $userPost)
//        {
//            $activity[$userPost['createdAt']->getTimestamp()] = array('type' => 'activity', 'time' => $userPost['createdAt'], 'userPost' => $userPost, 'user' => $userPost['user']);
//
//            if ($post['createdBy']['id'] == $userPost['user']['id'])
//            {
//                $activity[$userPost['createdAt']->getTimestamp()]['message'] = 'created this post.';
//                $activity[$userPost['createdAt']->getTimestamp()]['class'] = 'create';
//            }
//            else
//            {
//                $activity[$userPost['createdAt']->getTimestamp()]['message'] = 'jived with this post.';
//                $activity[$userPost['createdAt']->getTimestamp()]['class'] = 'jive';
//            }
//
//            if ($userPost['createdAt']->getTimestamp() != $userPost['updatedAt']->getTimestamp())
//            {
//                $activity[$userPost['updatedAt']->getTimestamp()] = array('type' => 'activity', 'time' => $userPost['updatedAt'], 'userPost' => $userPost, 'user' => $userPost['user'], 'message' => 'left.', 'class' => 'leave');
//            }
//        }
        foreach ($comments as $comment)
        {
            $activity[$comment['createdAt']->getTimestamp()] = array('type' => 'comment', 'comment' => $comment);
        }

        ksort($activity);
        return $activity;
    }

    /*
     * Disables all posts for the given user for today.
     */
    public function disableDailyPosts($user)
    {
        $post = $this->findPostBy(null, $user, date('Y-m-d 05:00:00', time()-(60*60*5)), 'Active', true);

        if ($post)
        {
            $this->deletePost($post);
        }
    }
}
