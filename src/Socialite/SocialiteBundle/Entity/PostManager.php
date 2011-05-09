<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Socialite\SocialiteBundle\Entity\UserManager;


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
    public function findObjectBy(array $criteria, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'pu', 'u'))
           ->from('Socialite\SocialiteBundle\Entity\Post', 'p')
           ->innerJoin('p.users', 'pu')
           ->innerJoin('pu.user', 'u');

        foreach ($criteria as $key => $val)
        {
            $qb->where('p.'.$key.' = :'.$key)
               ->setParameter($key, $val);
        }

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
    public function findPostsBy($user, $postTypes, $sortBy)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'pu', 'u'))
           ->from('Socialite\SocialiteBundle\Entity\Post', 'p')
           ->leftJoin('p.users', 'pu')
           ->innerJoin('pu.user', 'u')
           ->where('p.status = :status')
           ->setParameters(array(
               'status' => 'Active'
           ));

        if ($user)
        {
            // get the users this user is following
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select(array('u.id'))
               ->from('Socialite\SocialiteBundle\Entity\User', 'u')
               ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user')
               ->setParameters(array(
                   'user' => $user
               ));
            $query2 = $qb2->getQuery();
            $followingUsers = $query2->getArrayResult();
            $following = array();
            foreach ($followingUsers as $followingUser)
            {
                $following[] = $followingUser['id'];
            }

            if (count($following) == 0)
            {
                return array();
            }

            $qb->andwhere($qb->expr()->orx(
                $qb->expr()->in('pu.user', $following)
            ));
        }

        switch ($sortBy)
        {
            case 'Popularity':
                $qb->orderBy('o.score', 'DESC');
                break;
            default:
                $qb->orderBy('p.createdAt', 'DESC');
        }

        $query = $qb->getQuery();
        $objects = $query->getArrayResult();

        return $objects;
    }
    
    public function findMyPost($user, $status = 'Active')
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('up', 'p'))
           ->from('Socialite\SocialiteBundle\Entity\UsersPosts', 'up')
           ->innerJoin('up.post', 'p')
           ->where('up.status = :status AND up.createdAt >= :createdAt AND up.user = :createdBy')
           ->setParameters(array(
               'createdBy'    => $user,
               'createdAt'    => date('Y-m-d 05:00:00', time()-(60*60*5)),
               'status'       => $status
           ));

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $post = $query->getResult(Query::HYDRATE_OBJECT);

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
    public function togglePost($type, $user)
    {
        $result = array('status' => 'existing');
        $userPost = $this->findMyPost($user);

        if (!$userPost)
        {
            $post = $this->createPost();
            $post->setCreatedBy($user);
            $userPost = new UsersPosts();
            $userPost->setPost($post);
            $userPost->setUser($user);
            $this->em->persist($userPost);
            $result['status'] = 'new';
        }

        $userPost->getPost()->setType($type);

        $this->updatePost($userPost->getPost(), false);
        $this->em->flush();

        $result['post'] = $userPost;
        
        return $result;
    }

    /*
     * Check for a invite request. Return if found.
     *
     * @param integer $fromUser
     * @param integer $postId
     * @param bool $status
     * @param date $fromDate
     * @param bool $singleResult
     * @param bool $returnObject
     */
    public function findInviteRequests($fromUser, $postId, $status = false, $fromDate = null, $singleResult = false, $returnObject = false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('up', 'p.id'))
           ->from('Socialite\SocialiteBundle\Entity\UsersPosts', 'up')
           ->innerJoin('up.post', 'p')
           ->where('up.user = :user')
           ->setParameters(array(
               'user' => $fromUser
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
            return isset($connections[0]) ? $connections[0][0] : null;
        }

        return $connections;
    }

    public function toggleInviteRequest($fromUser, $postId, $go)
    {
        $response = array();

        // Check to see if this user has already sent out any invites today.
        $requests = $this->findInviteRequests($fromUser, null, 'Pending', date('Y-m-d 05:00:00', time()-(60*60*5)));
        if (count($requests) > 0 && $requests[0]['id'] != $postId && !$go)
        {
            $response['status'] = 'Check';

            return $response;
        }

        // Get the users connection to this post, if any.
        $connection = $this->findInviteRequests($fromUser, $postId, false, null, true, true);

        // Has the user already requested an invite to this post?
        if ($connection)
        {
            // Did they previously cancel it?
            if ($connection->getStatus() == 'Cancelled')
            {
                $response['status'] = 'new';

                // If it was previously approved, set it straight to active.
                if ($connection->getApproved())
                {
                    $connection->setStatus('Active');
                }
                // Else set the invite to pending again.
                else
                {
                    $connection->setStatus('Pending');
                }
            }
            else
            {
                $response['status'] = 'existing';
                $connection->setStatus('Cancelled');
            }
        }
        else
        {
            $fromUser = $this->em->getRepository('SocialiteBundle:User')->find($fromUser);
            $post = $this->em->getRepository('SocialiteBundle:Post')->find($postId);

            $response['status'] = 'new';

            $connection = new UsersPosts();
            $connection->setUser($fromUser);
            $connection->setPost($post);
            $connection->setStatus('Pending');
            $connection->setApproved(false);
        }

        $this->em->persist($connection);
        $this->em->flush();

        $response['flash'] = array('type' => 'success', 'message' => 'Invite request ' . ($response['status'] == 'existing' ? 'cancelled' : 'sent') .' successfully!');
        $response['newText'] = $response['status'] == 'existing' ? 'Request Invite' : 'Cancel Request';

        return $response;
    }
}
