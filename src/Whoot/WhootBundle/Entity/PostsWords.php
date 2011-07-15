<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
    
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @ORM\Entity
 * @ORM\Table(name="posts_words")
 * @ORM\HasLifecycleCallbacks
 */
class PostsWords
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Word", inversedBy="posts", cascade={"persist"})
     * @ORM\JoinColumn(name="word_id", referencedColumnName="id")
     */
    protected $word;

    /**
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Post", inversedBy="words", cascade={"persist"})
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id")
     */
    protected $post;

    public function __construct()
    {
        $this->words = new ArrayCollection();
        $this->posts = new ArrayCollection();
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
     * Set post
     *
     * @param integer $post
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * Get post
     *
     * @return integer $post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set word
     *
     * @param integer $word
     */
    public function setWord($word)
    {
        $this->word = $word;
    }

    /**
     * Get word
     *
     * @return integer $word
     */
    public function getWord()
    {
        return $this->word;
    }
}