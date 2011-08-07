<?php

namespace Whoot\WhootBundle\Document;

use Doctrine\ODM\MongoDB\DocumentManager;
use Whoot\WhootBundle\Model\ObjectManager as BaseManager;
use Whoot\WhootBundle\Util\DateConverter;

class InviteManager extends BaseManager
{
    public function __construct(DocumentManager $dm, $class)
    {
        parent::__construct($dm, $class);
    }

    public function createInvite()
    {
        return $this->createObject();
    }

    public function deleteInvite(Invite $invite, $andFlush = true)
    {
        return $this->deleteObject($invite, $andFlush);
    }

    public function updateInvite(Invite $invite, $andFlush = true)
    {
        return $this->updateObject($invite, $andFlush);
    }

    public function findInviteBy(array $criteria)
    {
        return $this->findObjectBy($criteria);
    }

    public function findInvitesBy(array $criteria, array $inCriteria = array(), $sorts = array(), $dateRange = null, $limit = null, $offset = 0)
    {
        return $this->findObjectsBy($criteria, $inCriteria, $sorts, $dateRange, $limit, $offset);
    }

    public function cancelInvite($invite)
    {
        $invite->setStatus('Cancelled');
        $this->updateInvite($invite);

        $this->m->Post->update(
            array('invite.invite' => $invite->getId()),
            array(
                '$set' =>
                    array(
                        'isCurrentPost' => false
                    )
            ),
            array('upsert' => false, 'multiple' => true)
        );
    }

    public function toggleAttendee($inviteId, $user)
    {
        $invite = $this->dm->createQueryBuilder($this->class)
            ->field('id')->equals($inviteId)
            ->getQuery()
            ->getSingleResult();

        $return = array();

        if ($invite)
        {
            $return['status'] = 'success';

            // Are we removing an attendee?
            if ($invite->findAttendee($user->getId()->__toString()))
            {
                $return['action'] = 'remove';
                $return['newText'] = '+ I\'m Attending';
                $this->m->Invite->update(
                    array('_id' => $invite->getId()),
                    array(
                        '$inc' =>
                            array(
                                'attendingCount' => -1
                            ),
                        '$unset' =>
                            array(
                                'attending.'.$user->getId()->__toString() => 1,
                            )
                    )
                );
            }
            // Else we are adding an attendee
            else
            {
                $return['action'] = 'add';
                $return['newText'] = '- Cancel Attending';
                $this->m->Invite->update(
                    array('_id' => $invite->getId()),
                    array(
                        '$inc' =>
                            array(
                                'attendingCount' => 1
                            ),
                        '$set' =>
                            array(
                                'attending.'.$user->getId()->__toString() => $user->getId(),
                            )
                    )
                );
            }

            $change = $invite->findAttendee($user->getId()->__toString()) ? -1 : 1;
            $invite->setAttendingCount($invite->getAttendingCount() + $change);

            $return['invite'] = $invite;
        }
        else
        {
            $return['status'] = 'error';
            $return['message'] = 'Woops, this invite was not found! [e-I01]';
        }

        return $return;
    }
}
