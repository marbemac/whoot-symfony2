<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;


class PostManager
{
    protected $em;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
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
    public function updatePost(Post $post)
    {
        $this->em->persist($post);
        $this->em->flush();
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

    /**
     * {@inheritDoc}
     */
    public function findObjectsBy(array $criteria)
    {
        return $this->em->getRepository('SocialiteBundle:Post')->findBy($criteria);
    }
    
    public function findTodaysPost($user)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('p'))
           ->from('Socialite\SocialiteBundle\Entity\Post', 'p')
           ->where('p.status = :status AND p.createdAt >= :createdAt AND p.createdBy = :createdBy')
           ->setParameters(array(
               'createdBy'    => $user,
               'createdAt'    => date('Y-m-d 05:00:00', time()),
               'status'       => 'Active'
           ));

        $query = $qb->getQuery();
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
        $post = $this->findTodaysPost($user);
                
        if (!$post)
        {
            $post = $this->createPost();
            $post->setCreatedBy($user);
            $result['status'] = 'new';
        }
        
        $post->setStatus('Active');
        $post->setType($type);
        $this->updatePost($post);
        
        $result['post'] = $post;
        
        return $result;
    }
}
