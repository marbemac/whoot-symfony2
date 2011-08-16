<?php

namespace Whoot\NotificationBundle;

use Marbemac\NotificationBundle\MarbemacNotificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WhootNotificationBundle extends Bundle
{
    public function getParent()
    {
        return 'MarbemacNotificationBundle';
    }
}
