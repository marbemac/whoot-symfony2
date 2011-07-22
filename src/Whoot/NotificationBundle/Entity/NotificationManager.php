<?php

namespace Whoot\NotificationBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Evario\NotificationBundle\Entity\NotificationInterface;
use Evario\NotificationBundle\Entity\NotificationManager as BaseNotificationManager;

class NotificationManager extends BaseNotificationManager
{
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, $serviceContainer, $class)
    {
        parent::__construct($em, $serviceContainer, $class);
    }

    protected function removePingNotification(NotificationInterface $notification, $dateRange, $andFlush)
    {
        $pings = $this->serviceContainer->get('whoot.manager.ping')->findPingsBy(array('user' => $notification->getAffectedUser()), $dateRange, null);

        if (count($pings) < 2)
        {
            $this->deleteNotification($notification, $andFlush);
        }
    }

    protected function generatePingNotification($notification)
    {
        $dateRange = $this->buildDateRange($notification['createdAt'], 0, 1);
        $pings = $this->serviceContainer->get('whoot.manager.ping')->findPingsBy(array('user' => $notification['affectedUser']['id']), $dateRange, null);

        $notificationData = array();
        $notificationData['notification'] = $notification;
        $notificationData['text'] = count($pings).(count($pings) > 1 ? ' people have ' : ' person ').'pinged you tonight!';

        return $notificationData;
    }

    protected function removeFollowNotification(NotificationInterface $notification, $dateRange, $andFlush)
    {
        $followers = $this->serviceContainer->get('whoot.manager.user')->getFollowers($notification->getAffectedUser(), $dateRange, null, null);

        if (count($followers) < 2)
        {
            $this->deleteNotification($notification, $andFlush);
        }
    }

    protected function generateFollowNotification($notification)
    {
        $dateRange = $this->buildDateRange($notification['createdAt'], 0, 1);
        $followers = $this->serviceContainer->get('whoot.manager.user')->getFollowers($notification['affectedUser']['id'], $dateRange, null, null);

        $notificationData = array();
        $notificationData['notification'] = $notification;
        $notificationData['text'] = $followers[0]['firstName'].' '.$followers[0]['lastName'];
        if (count($followers) == 2)
        {
            $notificationData['text'] .= ' and '.$followers[1]['firstName'].' '.$followers[1]['lastName'] . ' are following you';
        }
        else if (count($followers) > 2)
        {
            $notificationData['text'] .= ', '.$followers[1]['firstName'].' '.$followers[1]['lastName'] . ', and '.(count($followers)-2).' others are following you';
        }
        else
        {
            $notificationData['text'] .= ' is following you';
        }

        return $notificationData;
    }
}