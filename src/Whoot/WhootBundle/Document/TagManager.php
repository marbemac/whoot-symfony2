<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class TagManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createTag()
    {
        return $this->createObject();
    }

    public function deleteTag(Tag $tag, $andFlush = true)
    {
        return $this->deleteObject($tag, $andFlush);
    }

    public function updateTag(Tag $tag, $andFlush = true)
    {
        return $this->updateObject($tag, $andFlush);
    }

    public function findTagBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findTagsBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function getTrending($posts, $limit)
    {
        $tagCounts = array();
        foreach ($posts as $post)
        {
            foreach ($post->getTags() as $tag)
            {
                if (isset($tagCounts[$tag->getTag()->__toString()]))
                {
                    $tagCounts[$tag->getTag()->__toString()] += 1;
                }
                else
                {
                    $tagCounts[$tag->getTag()->__toString()] = 1;
                }
            }
        }

        arsort($tagCounts);

        $ids = array();
        foreach ($tagCounts as $key => $count)
        {
            $ids[] = new \MongoId($key);
        }

        $trendableTags = $this->m->Tag->find(
            array(
                'isTrendable' => true,
                '_id' => array(
                    '$in' => $ids
                )
            )
        );

        $trendableTags = iterator_to_array($trendableTags);

        $trendingTags = array();
        $found = 0;
        foreach ($tagCounts as $key => $tagCount)
        {
            if (isset($trendableTags[$key]) && $found < $limit)
            {
                $trendingTags[] = $trendableTags[$key];
                $found++;
            }
        }

        return $trendingTags;
    }
}
