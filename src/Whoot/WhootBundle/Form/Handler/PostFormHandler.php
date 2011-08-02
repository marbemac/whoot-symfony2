<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\Post;
use Whoot\WhootBundle\Document\PostManager;
use Whoot\WhootBundle\Document\TagManager;
use Whoot\WhootBundle\Util\SlugNormalizer;
use FOS\UserBundle\Document\UserManager;

class PostFormHandler
{
    protected $form;
    protected $request;
    protected $postManager;
    protected $tagManager;
    protected $userManager;

    public function __construct(Form $form, Request $request, PostManager $postManager, TagManager $tagManager, UserManager $userManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->postManager = $postManager;
        $this->tagManager = $tagManager;
        $this->userManager = $userManager;
    }

    public function process(Post $post = null, $createdBy)
    {
        if (null === $post) {
            $post = $this->postManager->createPost();
        }

        $this->form->setData($post);

        if ('POST' == $this->request->getMethod()) {
            $params = $this->request->request->all();

            if (!isset($params['whoot_post_form']))
            {
                return false;
            }

            // Validate the tags
            $tagCount = 0;
            $characterCount = 0;
            $tagError = false;
            foreach ($params['whoot_post_form']['tags'] as $tag)
            {
                if (strlen(trim($tag['name'])) > 0)
                {
                    $tags = explode(' ', trim($tag['name']));
                    $tagCount += count($tags);
                    $characterCount += strlen(implode('', $tags));
                }
            }

            if ($tagCount > 5)
            {
                $tagError = true;
                $error = new FormError('You can only use 5 words total (if a tag is "night dancing" that is two words)!');
                $this->form->addError($error);
            }

            if ($characterCount > 50)
            {
                $tagError = true;
                $error = new FormError('You can only use 50 characters total!');
                $this->form->addError($error);
            }

            if (!$tagError)
            {
                $this->form->bindRequest($this->request);

                if ($this->form->isValid())
                {
                    // Create the tags and links if necessary
                    $post->setTags(array());
                    $usedtags = array();
                    foreach ($params['whoot_post_form']['tags'] as $tag)
                    {
                        if (strlen(trim($tag['name'])) > 0)
                        {
                            $slug = new SlugNormalizer($tag['name']);
                            $foundTag = $this->tagManager->findTagBy(array('slug' => $slug->__toString()));
                            if (!$foundTag && !in_array($slug, $usedtags))
                            {
                                $foundTag = $this->tagManager->createTag();
                                $foundTag->setName($tag['name']);
                                $this->tagManager->updateTag($foundTag, false);
                                $usedtags[] = $slug;
                            }

                            $post->addTag($foundTag);
                        }
                    }

                    $oldPosts = $this->postManager->findPostsBy(array('createdBy' => $createdBy->getId(), 'isCurrentPost' => true), array(), array(), array('target' => 'createdAt', 'start' => date('Y-m-d 05:00:00', time()-(60*60*5))));
                    foreach ($oldPosts as $oldPost)
                    {

                        $oldPost->setIsCurrentPost(false);
                        $this->postManager->updatePost($oldPost, false);
                    }

                    $post->setCreatedBy($createdBy);
                    $this->postManager->updatePost($post);
                    $createdBy->setCurrentPost($post);
                    $this->userManager->updateUser($createdBy);

                    return true;
                }

            }

            return false;
        }
    }
}