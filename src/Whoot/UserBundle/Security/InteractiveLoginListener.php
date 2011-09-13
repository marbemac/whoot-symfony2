<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Whoot\UserBundle\Security;

use Whoot\UserBundle\Document\UserManager;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use DateTime;

class InteractiveLoginListener
{
    protected $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    public function onSecurityInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();
        $this->userManager->updateFollowingCounts($user, true);
    }
}
