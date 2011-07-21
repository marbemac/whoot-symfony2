<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Evario\NotificationBundle\Entity\NotificationManager as BaseNotificationManager;

class NotificationManager extends BaseNotificationManager
{
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $class)
    {
        parent::__construct($em, $class);
    }
}