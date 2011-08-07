<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class CommentManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createComment()
    {
        return $this->createObject();
    }

    public function deleteComment(Comment $comment, $andFlush = true)
    {
        return $this->deleteObject($comment, $andFlush);
    }

    public function updateComment(Comment $comment, $andFlush = true)
    {
        return $this->updateObject($comment, $andFlush);
    }

    public function findCommentBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findCommentsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }
}
