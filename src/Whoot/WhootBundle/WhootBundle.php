<?php

namespace Whoot\WhootBundle;

use FOS\UserBundle\FOSUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WhootBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
