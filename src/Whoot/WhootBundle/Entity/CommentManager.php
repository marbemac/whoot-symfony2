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
    public function findCommentsBy($postId, $inviteId, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('c', 'cb'))
           ->from('Whoot\WhootBundle\Entity\Comment', 'c')
           ->innerJoin('c.createdBy', 'cb')
           ->where('c.status = :status')
           ->setParameters(array(
               'status' => 'Active'
           ));

        if ($postId)
        {
            $qb->addSelect('p')
               ->innerJoin('c.post', 'p', 'WITH', 'p.id = :postId')
               ->setParameter('postId', $postId);
        }

        if ($inviteId)
        {
            $qb->addSelect('i')
               ->innerJoin('c.invite', 'i', 'WITH', 'i.id = :inviteId')
               ->setParameter('inviteId', $inviteId);
        }

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $comments = $query->getResult($returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY);

        return $comments;
    }
}
