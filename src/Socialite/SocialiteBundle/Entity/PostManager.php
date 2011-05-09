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
        $qb->select(array('p', 'cb'))
           ->from('Limelight\LimelightBundle\Entity\Post', 'p')
           ->innerJoin('p.createdBy', 'cb');

        foreach ($criteria as $key => $val)
        {
            $qb->where('o.'.$key.' = :'.$key)
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
    
    public function findMyPost($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p', 'u'))
           ->from('Socialite\SocialiteBundle\Entity\Post', 'p')
           ->innerJoin('p.users', 'u', 'WITH', 'u.user = :createdBy AND u.createdAt >= :createdAt')
           ->where('p.status = :status AND p.createdAt >= :createdAt')
           ->setParameters(array(
               'createdBy'    => $user,
               'createdAt'    => date('Y-m-d 05:00:00', time()),
               'status'       => 'Active'
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
        $post = $this->findMyPost($user);

        if (!$post)
        {
            $post = $this->createPost();
            $post->setCreatedBy($user);
            $userPost = new UsersPosts();
            $userPost->setPost($post);
            $userPost->setUser($user);
            $this->em->persist($userPost);
            $result['status'] = 'new';
        }

        $post->setStatus('Active');
        $post->setType($type);

        $user->setPost($post);

        $this->updatePost($post, false);
        $this->em->flush();

        $result['post'] = $post;
        
        return $result;
    }
}
