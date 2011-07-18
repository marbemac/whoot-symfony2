<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Entity\Invite;
use Whoot\WhootBundle\Entity\InviteManager;
use Whoot\WhootBundle\Entity\PostManager;

class InviteFormHandler
{
    protected $form;
    protected $request;
    protected $inviteManager;
    protected $postManager;

    public function __construct(Form $form, Request $request, InviteManager $inviteManager, PostManager $postManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->inviteManager = $inviteManager;
        $this->postManager = $postManager;
    }

    public function process(Invite $invite = null, $createdBy)
    {
        if (null === $invite) {
            $invite = $this->inviteManager->createInvite();
        }

        $invite->setSubdirectory('invites');
        $this->form->setData($invite);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid())
            {
                $invite->setCreatedBy($createdBy);
                $this->postManager->disableDailyPosts($createdBy);
                $post = $this->postManager->createPost();
                $post->setCreatedBy($createdBy);
                $post->setInvite($invite);
                $post->setType($invite->getType());
                $this->postManager->updatePost($post, false);
                $this->inviteManager->updateInvite($invite);

                return true;
            }

            return false;
        }
    }
}