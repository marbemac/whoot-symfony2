<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Entity\Post;
use Whoot\WhootBundle\Entity\CommentManager;
use Whoot\WhootBundle\Entity\PostManager;
use Whoot\WhootBundle\Entity\InviteManager;

class CommentFormHandler
{
    protected $form;
    protected $request;
    protected $commentManager;
    protected $postManager;
    protected $inviteManager;

    public function __construct(Form $form, Request $request, CommentManager $commentManager, PostManager $postManager, InviteManager $inviteManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->commentManager = $commentManager;
        $this->postManager = $postManager;
        $this->inviteManager = $inviteManager;
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
                if ($params['whoot_comment_form']['post'])
                {
                    $post = $this->postManager->findPostBy($params['whoot_comment_form']['post'], null, null, null, true);
                    $comment->setPost($post);
                }
                else if ($params['whoot_comment_form']['invite'])
                {
                    $invite = $this->inviteManager->findInviteBy($params['whoot_comment_form']['invite'], null, null, null, true);
                    $comment->setInvite($invite);
                }

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