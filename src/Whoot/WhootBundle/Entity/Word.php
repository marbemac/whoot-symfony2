<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;
use Whoot\WhootBundle\Util\SlugNormalizer;

/**
 * @ORM\Entity
 * @ORM\Table(name="word")
 * @ORM\HasLifecycleCallbacks
 */
class Word
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @var string $content
     * @ORM\Column(type="string")
     */
    protected $content;

    /**
     * @var string $slug
     * @ORM\Column(type="string")
     */
    protected $slug;

    /**
     * @var bool $trendable
     * @ORM\Column(type="boolean")
     */
    protected $trendable = true;

    /**
     * @var dateTime $createdAt
     * @ORM\Column(type="datetime", name="created_at", nullable=true)
     */
    protected $createdAt;

    /**
     * @var Post $posts
     *
     * @ORM\OneToMany(targetEntity="Whoot\WhootBundle\Entity\PostsWords", mappedBy="word")
     */
    protected $posts;


    public function __construct() {
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set content
     *
     * @param string $content
     */
    public function setContent($content)
    {
        $this->content = $content;
        $this->setSlug($content);
    }

    /**
     * Get content
     *
     * @return string $content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Given string, compute and set slug
     *
     * @param string $slug
     */
    public function setSlug($slug)
    {
        $this->slug = new SlugNormalizer($slug);
    }

    /**
     * Get slug
     *
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    public function getTrendable()
    {
        return $this->trendable;
    }

    public function setTrendable($trendable)
    {
        $this->trendable = $trendable;
    }

    /**
     * @return Post $post
     */
    public function getPosts()
    {
        return $this->posts;
    }

    /**
     * @param Post $post
     *
     * @return void
     */
    public function setPost($post)
    {
        $this->posts[] = $post;
    }

    /**
     * @ORM\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }
}