<?php

namespace Whoot\WhootBundle\Form\Handler;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\SecurityContext;

use Whoot\WhootBundle\Document\Invite;
use Whoot\WhootBundle\Document\InviteManager;
use Whoot\WhootBundle\Document\PostManager;
use Whoot\WhootBundle\Document\LocationManager;
use Whoot\UserBundle\Document\UserManager;
use Marbemac\ImageBundle\Document\ImageManager;

class InviteFormHandler
{
    protected $form;
    protected $request;
    protected $inviteManager;
    protected $postManager;
    protected $imageManager;
    protected $userManager;
    protected $locationManager;

    public function __construct(Form $form, Request $request, InviteManager $inviteManager, PostManager $postManager, ImageManager $imageManager, UserManager $userManager, LocationManager $locationManager)
    {
        $this->form = $form;
        $this->request = $request;
        $this->inviteManager = $inviteManager;
        $this->postManager = $postManager;
        $this->imageManager = $imageManager;
        $this->userManager = $userManager;
        $this->locationManager = $locationManager;
    }

    public function process(Invite $invite = null, $createdBy)
    {
        if (null === $invite) {
            $invite = $this->inviteManager->createInvite();
        }

        $this->form->setData($invite);

        if ('POST' == $this->request->getMethod()) {
            $this->form->bindRequest($this->request);

            if ($this->form->isValid())
            {
                $params = $this->request->request->all();
                if (!isset($params['whoot_invite_form']))
                {
                    return false;
                }
                $params = $params['whoot_invite_form'];

                // Disable old posts
                $this->postManager->disableDailyPosts($createdBy);

                // Update the users current location to the location of this post
                $createdBy = $this->locationManager->updateCurrentLocation($createdBy, $params['currentLocation']);

                // Create the invite
                $invite = $this->locationManager->updateCurrentLocation($invite, $params['currentLocation']);
                $invite->setCreatedBy($createdBy);
                if ($params['coordinates'])
                {
                    $coordinates = explode(':', $params['coordinates']);
                    $invite->setCoordinates($coordinates[0], $coordinates[count($coordinates)-1]);
                }

                // Save the image
                if ($_FILES['whoot_invite_form']['name']['image'])
                {
                    $img = $_FILES['whoot_invite_form']['tmp_name']['image'];
                    $image = $this->imageManager->saveImage($img, $createdBy->getId(), null, 'Invite', null, true, null, null, null);
                    $invite->setImage($image->getGroupId());
                }

                $this->inviteManager->updateInvite($invite);

                // Create the post
                $post = $this->postManager->createPost();
                $post->setInvite($invite);
                $post->setCreatedBy($createdBy->getId()->__toString());

                $this->postManager->updatePost($post);

                $createdBy->setCurrentPost($post);
                $this->userManager->updateUser($createdBy);

                $this->inviteManager->toggleAttendee($invite->getId()->__toString(), $createdBy);

                return true;
            }

            return false;
        }
    }
}