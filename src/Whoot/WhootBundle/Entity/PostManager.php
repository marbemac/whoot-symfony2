<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\WhootBundle\Entity\UserManager;


class PostManager
{
    protected $userManager;
    protected $em;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(UserManager $userManager, EntityManager $em)
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
    public function deletePost(Post $post)
    {
        $post->setStatus('Deleted');
        $this->em->persist($post);
        $this->em->flush();
        return array('result' => 'success');
    }

    /**
     * {@inheritDoc}
     */
    public function updatePost(Post $post, $andFlush = true)
    {
        $this->em->persist($post);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findPostBy($postId, $createdBy=null, $createdAt=null, $postStatus=null, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'cb', 'pu', 'u'))
           ->from('Whoot\WhootBundle\Entity\Post', 'p')
           ->innerJoin('p.createdBy', 'cb');

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
            $qb->innerJoin('p.users', 'pu', 'WITH', 'pu.user = :userId');
            $qb->setParameter('userId', $createdBy);
        }
        else
        {
            $qb->innerJoin('p.users', 'pu');
        }

//        $qb->setParameter('status', 'Active');
        $qb->innerJoin('pu.user', 'u');

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $hydrateMode = $returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $post = $query->getSingleResult($hydrateMode);

        return $post;
    }

    /*
     * The main way to get feeds. Returns an array of posts based on specified conditions.
     *
     * @param array $user Get objects of users this user is following.
     * @param array $postTypes Array of strings of posts types to include. Topic|Talk|News|Question|Procon|List|Video|Picture.
     * @param string $sortBy How do we want to sort the list? Popular|Newest|Controversial|Upcoming
     *
     * @return array $posts
     */
    public function findPostsBy($user, $postTypes, $sortBy, $createdAt)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p, count(pu.id) AS popularity', 'pu'))
           ->from('Whoot\WhootBundle\Entity\Post', 'p')
           ->innerJoin('p.users', 'pu', 'WITH', 'pu.status = :status')
           ->where('p.status = :status')
           ->groupBy('p.id')
           ->setParameters(array(
               'status' => 'Active',
           ));

        if ($user)
        {
            // get the users this user is following
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select(array('u.id'))
               ->from('Whoot\WhootBundle\Entity\User', 'u')
               ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user')
               ->setParameters(array(
                   'user' => $user
               ));
            $query2 = $qb2->getQuery();
            $followingUsers = $query2->getArrayResult();
            $following = array($user->getId());
            foreach ($followingUsers as $followingUser)
            {
                $following[] = $followingUser['id'];
            }

            $qb->andwhere($qb->expr()->in('pu.user', $following));
        }

        switch ($sortBy)
        {
            case 'popularity':
                $qb->orderBy('popularity', 'DESC');
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

        $query = $qb->getQuery();
        $objects = $query->getArrayResult();

        return $objects;
    }
    
    public function findMyPost($user, $status = 'Active', $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('up', 'p', 'u'))
           ->from('Whoot\WhootBundle\Entity\UsersPosts', 'up')
           ->innerJoin('up.post', 'p')
           ->leftJoin('p.users', 'u', 'WITH', 'u.status = :userPostStatus')
           ->where('up.status = :status AND up.createdAt >= :createdAt AND up.user = :createdBy')
           ->setParameters(array(
               'createdBy'    => $user,
               'createdAt'    => date('Y-m-d 05:00:00', time()-(60*60*5)),
               'status'       => $status,
               'userPostStatus' => 'Active'
           ));

        $query = $qb->getQuery();
        $post = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($post[0]) ? $post[0] : null;
    }
    
    /*
     * Creates a post for a user if the post does not yet exist today.
     * Updates the post if the user already has a post for the day.
     * 
     * @param string $type
     * @param User $user
     * 
     * @return array $result
     */
    public function togglePost($type, $note, $user)
    {
        $result = array('status' => 'existing');
        $userPost = $this->findMyPost($user, 'Active', true);
        if ($userPost)
        {
            $userPost->setStatus('Disabled');

            // If we have a previous post, and we are the only one connected to it, disable it.
            if (count($userPost->getPost()->getUsers()) == 1)
            {
                $userPost->getPost()->setStatus('Disabled');
                $this->updatePost($userPost->getPost(), false);
            }

            $this->em->persist($userPost);
        }

        $post = $this->createPost();
        $post->setType($type);
        $post->setNote($note);
        $post->setCreatedBy($user);
        $this->updatePost($post, false);

        $newUserPost = new UsersPosts();
        $newUserPost->setPost($post);
        $newUserPost->setUser($user);
        $this->em->persist($newUserPost);

        $this->em->flush();

        $result['status'] = 'new';
        $result['post'] = $newUserPost;
        
        return $result;
    }

    /*
     * Check for jives. Return if found.
     *
     * @param integer $fromUser
     * @param integer $postId
     * @param bool $status
     * @param date $fromDate
     * @param bool $singleResult
     * @param bool $returnObject
     */
    public function findJives($user, $postId, $status = false, $fromDate = null, $singleResult = false, $returnObject = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('up', 'p', 'u'))
           ->from('Whoot\WhootBundle\Entity\UsersPosts', 'up')
           ->innerJoin('up.post', 'p')
           ->innerJoin('p.users', 'u', 'WITH', 'u.status = :userPostStatus')
           ->where('up.user = :user')
           ->setParameters(array(
               'user' => $user,
               'userPostStatus' => 'Active'
           ));

        if ($postId)
        {
            $qb->andwhere('up.post = :post')
               ->setParameter('post', $postId);
        }

        if ($status)
        {
            $qb->andwhere('up.status = :status')
               ->setParameter('status', $status);
        }

        if ($fromDate)
        {
            $qb->andwhere('up.createdAt >= :fromDate')
               ->setParameter('fromDate', $fromDate);
        }

        $query = $qb->getQuery();
        $connections = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        if ($singleResult)
        {
            return isset($connections[0]) ? $connections[0] : null;
        }

        return $connections;
    }

    public function toggleJive($user, $postId, $go)
    {
        $response = array();

        // Check to see if this user has already jived today.
        $jive = $this->findJives($user, null, 'Active', date('Y-m-d 05:00:00', time()-(60*60*5)), true, true);

        if ($jive && $jive->getId() != $postId)
        {
            if (!$go)
            {
                $response['status'] = 'Check';

                return $response;
            }

            $jive->setStatus('Disabled');

            // If we have a previous post, and we are the only one connected to it, disable it.
            if (count($jive->getPost()->getUsers()) == 1)
            {
                $response['oldPostId'] = $jive->getPost()->getId();
                $jive->getPost()->setStatus('Disabled');
                $this->updatePost($jive->getPost(), false);
            }
        }

        $post = $this->findPostBy($postId, null, null, null, true);
        $response['status'] = 'new';

        $connection = new UsersPosts();
        $connection->setUser($user);
        $connection->setPost($post);

        $this->em->persist($jive);
        $this->em->persist($connection);
        $this->em->flush();

        $response['flash'] = array('type' => 'success', 'message' => 'Woot. Jive Successful!');

        return $response;
    }

    /**
     * Given a post data structure, extract and sort it's activity.
     *
     * @param Post $post Must include the post.createdBy, post.users, and post.users.user
     *
     * @return array $activity
     */
    public function buildActivity($post)
    {
        $activity = array();
        foreach ($post['users'] as $userPost)
        {
//            echo '<pre>';
//            var_dump($userPost);
//            echo '</pre>';
//            echo $userPost['id'] . ' * '. $userPost['createdAt']->getTimestamp().' *** '.$userPost['updatedAt']->getTimestamp().' ** ';
            $activity[$userPost['createdAt']->getTimestamp()] = array('time' => $userPost['createdAt'], 'userPost' => $userPost, 'user' => $userPost['user']);

            if ($post['createdBy']['id'] == $userPost['user']['id'])
            {
                $activity[$userPost['createdAt']->getTimestamp()]['message'] = 'created this post.';
                $activity[$userPost['createdAt']->getTimestamp()]['class'] = 'create';
            }
            else
            {
                $activity[$userPost['createdAt']->getTimestamp()]['message'] = 'jived with this post.';
                $activity[$userPost['createdAt']->getTimestamp()]['class'] = 'jive';
            }

            if ($userPost['createdAt']->getTimestamp() != $userPost['updatedAt']->getTimestamp())
            {
                $activity[$userPost['updatedAt']->getTimestamp()] = array('time' => $userPost['updatedAt'], 'userPost' => $userPost, 'user' => $userPost['user'], 'message' => 'left.', 'class' => 'leave');
            }
        }
        krsort($activity);
        return $activity;
    }
}
