<?php

namespace Whoot\WhootBundle\Security\User\Provider;

use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use \BaseFacebook;
use \FacebookApiException;

class FacebookProvider implements UserProviderInterface
{
    /**
     * @var \Facebook
     */
    protected $facebook;
    protected $userManager;
    protected $imageManager;
    protected $validator;

    public function __construct(BaseFacebook $facebook, $userManager, $imageManager, $validator)
    {
        $this->facebook = $facebook;
        $this->userManager = $userManager;
        $this->imageManager= $imageManager;
        $this->validator = $validator;
    }

    public function supportsClass($class)
    {
        return $this->userManager->supportsClass($class);
    }

    public function findUserByFbId($fbId)
    {
        return $this->userManager->findUserBy(array('facebookId' => $fbId));
    }

    public function loadUserByUsername($username)
    {
        $user = $this->findUserByFbId($username);

        // Try just getting by username
        if (!$user)
        {
            $user = $this->userManager->findUserBy(array('username' => $username));
        }

        $fbdata = null;
        if (!$user && $this->facebook->getUser())
        {
            try {
                $fbdata = $this->facebook->api('/me');
            } catch (FacebookApiException $e) {
                $fbdata = null;
            }
        }

        if (!$user && $fbdata) {

            if (empty($user)) {
                $user = $this->userManager->findUserBy(array('email' => $fbdata['email']));

                if (empty($user)) {
                    $pass = uniqid('up');

                    $user = $this->userManager->createUser();
                    $user->setUsername($fbdata['username']);
                    $user->setEnabled(true);
                    $user->setPlainPassword($pass);
                    $this->userManager->updatePassword($user);
                }
            }

            // TODO use http://developers.facebook.com/docs/api/realtime
            $user->setFBData($fbdata);

            if (count($this->validator->validate($user, 'Facebook'))) {
                // TODO: the user was found obviously, but doesnt match our expectations, do something smart
                throw new UsernameNotFoundException('The facebook user could not be stored');
            }
            $this->userManager->updateUser($user);

            // Get and save their fb profile image
            if ($fbdata && !$user->getCurrentProfileImage())
            {
                $url = 'http://graph.facebook.com/'.$fbdata['username'].'/picture?type=large';
                $tmpLocation = '/tmp/'.uniqid('fi');
                file_put_contents($tmpLocation, file_get_contents($url));
                $image = $this->imageManager->saveImage($tmpLocation, $user->getId(), null, 'user-profile', null, true, null, null, null);
                if ($image)
                {
                    $user->setCurrentProfileImage($image->getGroupId());
                    $this->userManager->updateUser($user);
                }
            }
        }

        if (empty($user)) {
            throw new UsernameNotFoundException('The user is not authenticated on facebook');
        }

        return $user;
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$this->supportsClass(get_class($user)) || !$user->getUsername()) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        return $this->loadUserByUsername($user->getUsername());
    }
}