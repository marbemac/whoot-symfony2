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

    public function updateWord(Word $word, $andFlush = true)
    {
        $this->em->persist($word);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    public function findWordBy($content, array $criteria, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('w'))
           ->from('Whoot\WhootBundle\Entity\Word', 'w');

        if ($content)
        {
            $slug = new SlugNormalizer($content);
            $qb->andWhere('w.slug = :slug')
               ->setParameter('slug', $slug);
        }

        foreach ($criteria as $key => $val)
        {
            $qb->andWhere('w.'.$key.' = :'.$key);
        }
        $qb->setParameters($criteria);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $word = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return isset($word[0]) ? $word[0] : null;
    }

    public function findWordsBy($content, $returnObject=false)
    {
        $slug = new SlugNormalizer($content);
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('w'))
           ->from('Whoot\WhootBundle\Entity\Word', 'w')
           ->where('w.slug = :slug')
           ->setParameter('slug', $slug);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $words = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return $words;
    }

    public function linkPostWord(Post $post, Word $word)
    {
        $pw = new PostsWords();
        $pw->setPost($post);
        $pw->setWord($word);
        $word->setScore($word->getScore()+1);
        $this->em->persist($pw);
    }

    public function getTrending($location, $createdAt, $limit, array $extraCriteria)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('w, count(w.id) AS popularity'))
           ->from('Whoot\WhootBundle\Entity\Word', 'w')
           ->innerJoin('w.posts', 'pw')
           ->innerJoin('pw.post', 'p', 'WITH', 'p.status = :status')
           ->setParameters(array(
               'status' => 'Active'
           ))
           ->setMaxResults($limit)
           ->orderBy('popularity', 'DESC')
           ->groupBy('w.id');

        if ($location)
        {
            $qb->andWhere('p.location = :location')
               ->setParameter('location', $location);
        }

        if ($createdAt)
        {
            $qb->andWhere('p.createdAt >= :createdAt')
               ->setParameter('createdAt', $createdAt);
        }

        if ($limit)
        {
            $qb->setMaxResults($limit);
        }

        foreach ($extraCriteria as $key => $val)
        {
            $qb->andWhere('w.'.$key.' = :'.$key);
        }
        $qb->setParameters($extraCriteria);

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $words = $query->getArrayResult();

        return $words;
    }
}
