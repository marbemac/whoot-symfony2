<?php

namespace Whoot\NotificationBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;

use Marbemac\NotificationBundle\Document\NotificationInterface;
use Marbemac\NotificationBundle\Document\NotificationManager as BaseNotificationManager;

class NotificationManager extends BaseNotificationManager
{

    protected $notificationTypes = array(
        'Follow',
        'Ping',
        'Comment'
    );

    protected $notificationThemes = array(
        'Follow' => 'full',
        'Ping' => 'count',
        'Comment' => 'full'
    );

    protected $notificationVerbs = array(
        'Follow' => array('singular' => 'is following you', 'plural' => 'are following you'),
        'Ping' => array('singular' => 'pinged you', 'plural' => 'pinged you'),
        'Comment' => array('singular' => 'commented on your', 'plural' => 'commented on your')
    );

    public function __construct(DocumentManager $dm, $router, $class, $maxContributorShow, $userRoute, $userRouteParameter)
    {
        parent::__construct($dm, $router, $class, $maxContributorShow, $userRoute, $userRouteParameter);
    }

    
//
//
//    protected function removePingNotification(NotificationInterface $notification, $dateRange, $andFlush)
//    {
//        $pings = $this->serviceContainer->get('whoot.manager.ping')->findPingsBy(array('user' => $notification->getAffectedUser()), $dateRange, null);
//
//        if (count($pings) < 2)
//        {
//            $this->deleteNotification($notification, $andFlush);
//        }
//    }
//
//    protected function generatePingNotification($notification)
//    {
//        if (is_object($notification))
//        {
//            $createdAt = $notification->getCreatedAt();
//            $affectedUserId = $notification->getAffectedUser()->getId();
//        }
//        else
//        {
//            $createdAt = $notification['createdAt'];
//            $affectedUserId = $notification['affectedUser']['id'];
//        }
//
//        $dateRange = $this->buildDateRange($createdAt, 0, 1);
//        $pings = $this->serviceContainer->get('whoot.manager.ping')->findPingsBy(array('user' => $affectedUserId), $dateRange, null);
//
//        $notificationData = array();
//        $notificationData['notification'] = $notification;
//        $notificationData['count'] = count($pings);
//        $notificationData['subject'] = $notificationData['count'] . ' new ping'.($notificationData['count'] > 1 ? 's' : '');
//        $notificationData['text'] = $notificationData['count'].($notificationData['count'] > 1 ? ' people have ' : ' person ').'pinged you tonight!';
//
//        return $notificationData;
//    }
//
//    protected function removeFollowNotification(NotificationInterface $notification, $dateRange, $andFlush)
//    {
//        $followers = $this->serviceContainer->get('whoot.manager.user')->getFollowers($notification->getAffectedUser(), $dateRange, null, null);
//
//        if (count($followers) < 2)
//        {
//            $this->deleteNotification($notification, $andFlush);
//        }
//    }
//
//    protected function generateFollowNotification($notification)
//    {
//        if (is_object($notification))
//        {
//            $createdAt = $notification->getCreatedAt();
//            $affectedUserId = $notification->getAffectedUser()->getId();
//        }
//        else
//        {
//            $createdAt = $notification['createdAt'];
//            $affectedUserId = $notification['affectedUser']['id'];
//        }
//
//        $dateRange = $this->buildDateRange($createdAt, 0, 1);
//        $followers = $this->serviceContainer->get('whoot.manager.user')->getFollowers($affectedUserId, $dateRange, null, null);
//
//        $notificationData = array();
//        $notificationData['notification'] = $notification;
//        $notificationData['count'] = count($followers);
//        $notificationData['subject'] = $notificationData['count'] . ' more '.($notificationData['count'] > 1 ? 'people' : 'person').' following you';
//        $notificationData['text'] = $followers[0]['firstName'].' '.$followers[0]['lastName'];
//
//        if ($notificationData['count'] == 2)
//        {
//            $notificationData['text'] .= ' and '.$followers[1]['firstName'].' '.$followers[1]['lastName'] . ' are following you';
//        }
//        else if ($notificationData['count'] > 2)
//        {
//            $notificationData['text'] .= ', '.$followers[1]['firstName'].' '.$followers[1]['lastName'] . ', and '.($notificationData['count']-2).' others are following you';
//        }
//        else
//        {
//            $notificationData['text'] .= ' is following you';
//        }
//
//        return $notificationData;
//    }
}