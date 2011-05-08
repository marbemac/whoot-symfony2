<?php

namespace Socialite\SocialiteBundle;

use FOS\UserBundle\FOSUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class SocialiteBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
