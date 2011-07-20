<?php

namespace Whoot\UserBundle;

use FOS\UserBundle\FOSUserBundle as FOSUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WhootUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
