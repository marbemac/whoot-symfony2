<?php

namespace Whoot\WhootBundle\Form;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Entity\Post;
use Whoot\WhootBundle\Entity\CommentManager;
use Whoot\WhootBundle\Entity\PostManager;

class CommentFormHandler
{
    protected $form;
    protected $request;
    protected $commentManager;
    protected $postManager;

    public function __construct(Form $form, Request $request, CommentManager $commentManager, PostManager $postManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->commentManager = $commentManager;
        $this->postManager = $postManager;
    }

    public function process(Comment $comment = null)
    {
        if (null === $comment) {
            $comment = $this->commentManager->createComment();
        }

        $this->form->setData($comment);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {

                $params = $this->request->request->all();
                $post = $this->postManager->findPostBy($params['comment']['post'], null, null, null, true);

                $comment->setPost($post);
                $this->commentManager->updateComment($comment);

                return true;
            }
            else
            {
                return false;
            }
        }
    }
}