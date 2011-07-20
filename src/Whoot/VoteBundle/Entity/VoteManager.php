<?php

namespace Whoot\VoteBundle\Entity;

use Whoot\UserBundle\Entity\UserManager;
use Whoot\WhootBundle\Entity\PostManager;
use Whoot\WhootBundle\Entity\InviteManager;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Symfony\Component\Security\Core\SecurityContext;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class VoteManager
{
    protected $em;
    protected $repository;
    protected $securityContext;
    protected $userManager;
    protected $postManager;
    protected $inviteManager;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     * @param EntityManager           $em
     * @param EntityManager           $em
     */
    public function __construct(EntityManager $em, SecurityContext $securityContext, UserManager $userManager, PostManager $postManager)
    {
        $this->em = $em;
        $this->securityContext = $securityContext;
        $this->userManager = $userManager;
        $this->postManager = $postManager;
    }

    /**
     * Creates a vote, sets user to current user, and returns vote.
     *
     * @return Limelight\VoteBundle\Entity\Vote $vote
     */
    public function createVote()
    {
        $vote = new Vote();

        return $vote;
    }

    /**
     * Persist a Vote.
     *
     * @param Vote $vote
     */
    public function updateVote(Vote $vote)
    {
        $this->em->persist($vote);
        $this->em->flush();
    }

    /**
     * @param array $criteria
     * @return Vote
     */
    public function findVoteBy(array $criteria)
    {
        return $this->em->getRepository('WhootVoteBundle:Vote')->findOneBy($criteria);
    }

    /**
     * Create and save a vote.
     *
     * @param string $objectType
     * @param integer $objectId
     * @param integer $amount Valid values are 1, 0, and -1
     * @return array Format: array('result' => 'success|error', 'message' => string, 'newScore' => integer)
     */
    public function addVote($objectType, $objectId, $amount)
    {
        // Make sure it's a valid amount.
        if (!in_array($amount, array(1)))
        {
            return array('result' => 'error', 'message' => 'Voting error [c: v1]');
        }

        switch($objectType)
        {
            case 'Post':
                $object = $this->postManager->findPostBy($objectId, null, null, null, true);
                break;
            case 'Invite':
//                $object = $this->objectManager->findObjectBy(array('id' => $objectId), true, false);
                break;
        }

        // Don't let the user vote on its own object!
        if ($this->securityContext->isGranted('OWNER', $object))
        {
            return array('result' => 'error', 'message' => 'You may not vote on your own submissions!');
        }

        $voter = $this->securityContext->getToken()->getUser();
        $affectedUser = $object->getCreatedBy();

        $vote = $this->findVoteBy(array(strtolower($objectType) => $object->getId(), 'voter' => $voter->getId()));

        // If the user has voted and the amount they voted is equal to this amount, set it to 0 because they must be unvoting.
        if ($vote && $vote->getAmount() == $amount)
        {
            $amount = 0;
        }

        // Did we find a previous vote on this object by this user?
        if ($vote)
        {
            $oldVoteAmount = $vote->getAmount();
            if ($amount == 0)
            {
                $vote->setStatus('Deleted');
            }
            else
            {
                $vote->setStatus('Active');
            }
            $vote->setAmount($amount);
            $this->updateVote($vote);

            $object->setScore($object->getScore() - $oldVoteAmount + $amount);
            $affectedUser->setScore($affectedUser->getScore() - $oldVoteAmount + $amount);
        }
        // Else create the vote if the amount is not zero.
        else if ($amount != 0)
        {
            $vote = $this->createVote();
            $vote->setVoter($voter);
            switch($objectType)
            {
                case 'Post':
                    $vote->setPost($object);
                    $vote->setType('Post');
                    break;
                case 'Invite':
                    $vote->setInvite($object);
                    $vote->setType('Invite');
                    break;
            }
            $vote->setAffectedUser($object->getCreatedBy());
            $vote->setAmount($amount);
            $this->updateVote($vote);

            $object->setScore($object->getScore() + $amount);
            $affectedUser->setScore($affectedUser->getScore() + $amount);
        }
        
        switch($objectType)
        {
            case 'Post':
                $this->postManager->updatePost($object);
                break;
            case 'Invite':
//                $object = $this->objectManager->findObjectBy(array('id' => $objectId), true, false);
                break;
        }

        return array(
            'result' => 'success',
            'message' => 'Vote updated!',
            'objectNewScore' => $object->getScore(),
            'objectId' => $object->getId(),
            'affectedUserNewScore' => $affectedUser->getScore(),
            'affectedUserId' => $affectedUser->getId()
        );
    }
}
