<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\Post;
use Whoot\WhootBundle\Document\PostManager;
use Whoot\WhootBundle\Document\LocationManager;
use Whoot\WhootBundle\Document\TagManager;
use Whoot\WhootBundle\Util\SlugNormalizer;
use Whoot\UserBundle\Document\UserManager;

class PostFormHandler
{
    protected $form;
    protected $request;
    protected $postManager;
    protected $tagManager;
    protected $userManager;
    protected $locationManager;

    public function __construct(Form $form, Request $request, PostManager $postManager, TagManager $tagManager, UserManager $userManager, LocationManager $locationManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->postManager = $postManager;
        $this->tagManager = $tagManager;
        $this->userManager = $userManager;
        $this->locationManager = $locationManager;
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

            $params = $params['whoot_post_form'];

            // Validate the tags
            $tagCount = 0;
            $characterCount = 0;
            $tagError = false;
            foreach ($params['tags'] as $tag)
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
                    $usedTags = array();
                    foreach ($params['tags'] as $tag)
                    {
                        if (strlen(trim($tag['name'])) > 0)
                        {
                            $slug = new SlugNormalizer($tag['name']);
                            $foundTag = $this->tagManager->findTagBy(array('slug' => $slug->__toString()));
                            // Is it a new tag?
                            if (!$foundTag)
                            {
                                $used = false;
                                foreach ($usedTags as $usedTag)
                                {
                                    if ($usedTag->getSlug() == $slug)
                                    {
                                        $used = true;
                                        $foundTag = $usedTag;
                                    }
                                }

                                if (!$used)
                                {
                                    $foundTag = $this->tagManager->createTag();
                                    $foundTag->setName($tag['name']);
                                    $this->tagManager->updateTag($foundTag, false);
                                    $usedTags[] = $foundTag;
                                }
                            }

                            $post->addTag($foundTag);
                        }
                    }

                    // Disable old posts
                    $this->postManager->disableDailyPosts($createdBy);

                    // Update the users current location to the location of this post
                    $createdBy = $this->locationManager->updateCurrentLocation($createdBy, $params['currentLocation']);

                    // Add the post location
                    $post = $this->locationManager->updateCurrentLocation($post, $params['currentLocation']);
                    
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