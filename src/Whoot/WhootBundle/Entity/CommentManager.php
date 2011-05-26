<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\Security\Core\SecurityContext;
    
class CommentManager
{
    protected $em;
    protected $securityContext;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, SecurityContext $securityContext)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
    }

    public function createComment()
    {
        $comment = new Comment();
        $comment->setCreatedBy($this->securityContext->getToken()->getUser());
        return $comment;
    }

    function deleteComment(Comment $comment)
    {
        $comment->setStatus('Deleted');
        $this->em->persist($comment);
        $this->em->flush();
    }

    function updateComment(Comment $comment, $andFlush=true)
    {
        $this->em->persist($comment);

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
        $qb->select(array('o', 't', 'cb', 'cf', 'ct'))
           ->from('Limelight\LimelightBundle\Entity\Object', 'o')
           ->innerJoin('o.Talk', 't')
           ->innerJoin('o.createdBy', 'cb')
           ->leftJoin('o.connectedFrom', 'cf', 'WITH', $qb->expr()->eq('cf.type', ':connectionType'))
           ->leftJoin('cf.connectedFrom', 'ct', 'WITH', $qb->expr()->eq('ct.status', ':objectStatus'))
           ->setParameters(array(
               'connectionType' => 'Node',
               'objectStatus' => 'Active'
           ));

        foreach ($criteria as $key => $val)
        {
            $qb->where('o.'.$key.' = :'.$key)
               ->setParameter($key, $val);
        }

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $hydrateMode = $returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $talk = $query->getSingleResult($hydrateMode);

        return $talk;
    }

    /**
     * {@inheritDoc}
     */
    public function findObjectsBy(array $criteria)
    {
        return $this->objectManager->findObjectsBy($criteria);
    }
}
