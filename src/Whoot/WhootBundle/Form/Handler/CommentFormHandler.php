<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\Post;
use Whoot\WhootBundle\Document\CommentManager;
use Whoot\WhootBundle\Document\PostManager;
use Whoot\WhootBundle\Document\InviteManager;

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

    public function process(Comment $comment = null, $createdBy)
    {
        if (null === $comment) {
            $comment = $this->commentManager->createComment();
        }

        $this->form->setData($comment);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid()) {

                $params = $this->request->request->all();
                $params = $params['whoot_comment_form'];

                if ($params['post'])
                {
                    $comment->setPost($params['post']);
                }
                else if ($params['invite'])
                {
                    $comment->setInvite($params['invite']);
                }

                $comment->setCreatedBy($createdBy->getId()->__toString());

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