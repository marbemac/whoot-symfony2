<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Whoot\WhootBundle\Util\SlugNormalizer;

class WordManager
{
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    public function createWord()
    {
        $word = new Word();
        return $word;
    }

    public function findWordBy($content, $returnObject=false)
    {
        $slug = new SlugNormalizer($content);
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('w'))
           ->from('Whoot\WhootBundle\Entity\Word', 'w')
           ->where('w.slug = :slug')
           ->setParameter('slug', $slug);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        try {
            $word = $query->getSingleResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);
        } catch (\Doctrine\Orm\NoResultException $e) {
            $word = null;
        }

        return $word;
    }

    public function linkPostWord(Post $post, Word $word)
    {
        $pw = new PostsWords();
        $pw->setPost($post);
        $pw->setWord($word);
        $this->em->persist($pw);
    }
}
