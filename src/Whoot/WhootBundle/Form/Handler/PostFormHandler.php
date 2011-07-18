<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Entity\Post;
use Whoot\WhootBundle\Entity\PostManager;
use Whoot\WhootBundle\Entity\WordManager;

class PostFormHandler
{
    protected $form;
    protected $request;
    protected $postManager;
    protected $wordManager;

    public function __construct(Form $form, Request $request, PostManager $postManager, WordManager $wordManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->postManager = $postManager;
        $this->wordManager = $wordManager;
    }

    public function process(Post $post = null, $createdBy)
    {
        if (null === $post) {
            $post = $this->postManager->createPost();
        }

        $this->form->setData($post);

        if ('POST' == $this->request->getMethod()) {
            $params = $this->request->request->all();

            // Validate the words
            $wordCount = 0;
            $characterCount = 0;
            $wordError = false;
            foreach ($params['whoot_post_form']['words'] as $word)
            {
                if (strlen(trim($word['content'])) > 0)
                {
                    $words = explode(' ', trim($word['content']));
                    $wordCount += count($words);
                    $characterCount += strlen(implode('', $words));
                }
            }

            if ($wordCount > 5)
            {
                $wordError = true;
                $error = new FormError('You can only use 5 words total!');
                $this->form->addError($error);
            }

            if ($characterCount > 50)
            {
                $wordError = true;
                $error = new FormError('You can only use 50 characters for your 5 words!');
                $this->form->addError($error);
            }

            if (!$wordError)
            {
                $this->form->bindRequest($this->request);

                if ($this->form->isValid())
                {
                    // Create the words and links if necessary
                    $usedWords = array();
                    foreach ($params['whoot_post_form']['words'] as $word)
                    {
                        if (strlen(trim($word['content'])) > 0)
                        {
                            $found = $this->wordManager->findWordBy($word['content'], array(), true);
                            if (!$found && !in_array(trim($word['content']), $usedWords))
                            {
                                $found = $this->wordManager->createWord();
                                $found->setContent($word['content']);
                                $usedWords[] = trim($word['content']);
                            }

                            $this->wordManager->linkPostWord($post, $found);
                        }
                    }

                    $post->setWords(null);
                    $post->setCreatedBy($createdBy);
                    $this->postManager->disableDailyPosts($createdBy);
                    $this->postManager->updatePost($post);

                    return true;
                }

            }

            return false;
        }
    }
}