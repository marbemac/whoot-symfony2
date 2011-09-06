<?php

namespace Whoot\UserBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;

class UserInviteManager extends BaseManager
{
    protected $userManager;

    public function __construct(DocumentManager $dm, $class, $userManager)
    {
        parent::__construct($dm, $class);

        $this->userManager = $userManager;
    }

    public function createUserInvite()
    {
        return $this->createObject();
    }

    public function deleteUserInvite(UserInvite $userInvite, $andFlush = true)
    {
        return $this->deleteObject($userInvite, $andFlush);
    }

    public function updateUserInvite(UserInvite $userInvite, $andFlush = true)
    {
        return $this->updateObject($userInvite, $andFlush);
    }

    public function findUserInviteBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findUserInvitesBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function generateFollows($user)
    {
        $invites = $this->findUserInviteBy(array('email' => $user->getEmail()));
        if ($invites)
        {
            $inviters = $this->userManager->findUsersBy(array('status' => 'Active'), array('id' => $invites->getInviters()));
            foreach ($inviters as $inviter)
            {
                $this->userManager->toggleFollow($user, $inviter->getId(), 'add');
                $this->userManager->toggleFollow($inviter, $user->getId(), 'add');
            }
        }
    }
}
