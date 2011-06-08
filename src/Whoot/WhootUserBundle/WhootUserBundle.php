<?php

namespace Limelight\LimelightUserBundle;

use FOS\UserBundle\FOSUserBundle as FOSUserBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class LimelightUserBundle extends Bundle
{
    public function getParent()
    {
        return 'FOSUserBundle';
    }
}
