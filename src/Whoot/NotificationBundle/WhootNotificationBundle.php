<?php

namespace Whoot\NotificationBundle;

use Evario\NotificationBundle\EvarioNotificationBundle as EvarioNotificationBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class WhootNotificationBundle extends Bundle
{
    public function getParent()
    {
        return 'EvarioNotificationBundle';
    }
}
