<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use Whoot\UserBundle\Entity\UserManager;

class InviteManager
{
    protected $userManager;
    protected $em;
    
    /**
     * Constructor.
     *
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em, UserManager $userManager)
    {
        $this->em = $em;
        $this->userManager = $userManager;
    }

    public function createInvite()
    {
        $invite = new Invite();
        return $invite;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteInvite(Invite $invite)
    {
        $invite->setStatus('Deleted');
        $this->em->persist($invite);
        $this->em->flush();
        return array('result' => 'success');
    }

    /**
     * {@inheritDoc}
     */
    public function updateInvite(Invite $invite, $andFlush = true)
    {
        $this->em->persist($invite);

        if ($andFlush)
        {
            $this->em->flush();
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findInviteBy($inviteId, $createdBy=null, $createdAt=null, $status=null, $returnObject=false)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('i', 'cb', 'ip', 'u'))
           ->from('Whoot\WhootBundle\Entity\Invite', 'i')
           ->innerJoin('i.createdBy', 'cb')
           ->innerJoin('i.posts', 'ip', 'WITH', 'ip.status = :status')
           ->innerJoin('ip.createdBy', 'u')
           ->setParameter('status', 'Active');

        if ($inviteId)
        {
            $qb->andWhere('i.id = :inviteId');
            $qb->setParameter('inviteId', $inviteId);
        }

        if ($status)
        {
            $qb->andWhere('i.status = :status');
            $qb->setParameter('status', $status);
        }

        if ($createdAt)
        {
            $qb->andWhere('i.createdAt >= :createdAt');
            $qb->setParameter('createdAt', $createdAt);
        }

        if ($createdBy)
        {
            $qb->andWhere('i.createdBy = :createdBy')
               ->setParameter('createdBy', $createdBy);
        }

        $query = $qb->getQuery();
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);
        $hydrateMode = $returnObject ? Query::HYDRATE_OBJECT : Query::HYDRATE_ARRAY;
        $invite = $query->getSingleResult($hydrateMode);

        return $invite;
    }

    /*
     * The main way to get feeds. Returns an array of invites based on specified conditions.
     *
     * @param array $user Get objects of users this user is following.
     * @param array $postTypes Array of strings of posts types to include. Topic|Talk|News|Question|Procon|List|Video|Picture.
     * @param string $sortBy How do we want to sort the list? Popular|Newest|Controversial|Upcoming
     * @param date $createdAt
     * @param integer $offset
     * @param integer $limit
     *
     * @return array $posts
     */
    public function findInvitesBy($user, $postTypes, $sortBy, $createdAt, $offset, $limit)
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select(array('i', 'cb'))
           ->from('Whoot\WhootBundle\Entity\Invite', 'i')
           ->innerJoin('i.createdBy', 'cb')
           ->where('i.status = :status')
           ->setParameters(array(
               'status' => 'Active'
           ));

        if ($user)
        {
            // get the users this user is following
            $qb2 = $this->em->createQueryBuilder();
            $qb2->select(array('u.id'))
               ->from('Whoot\UserBundle\Entity\User', 'u')
               ->innerJoin('u.followers', 'f', 'WITH', 'f.user = :user AND f.status = :status')
               ->setParameters(array(
                   'user' => $user,
                   'status' => 'Active'
               ));
            $query2 = $qb2->getQuery();
            $followingUsers = $query2->getArrayResult();
            $following = array($user->getId());
            foreach ($followingUsers as $followingUser)
            {
                $following[] = $followingUser['id'];
            }

            $qb->andwhere($qb->expr()->in('cb.id', $following));
        }

        switch ($sortBy)
        {
            case 'popularity':
                $qb->orderBy('i.attending', 'DESC');
                $qb->addOrderBy('i.createdAt', 'DESC');
                break;
            default:
                $qb->orderBy('i.createdAt', 'DESC');
        }

        if ($createdAt)
        {
            $qb->andWhere('i.createdAt >= :createdAt');
            $qb->setParameter('createdAt', $createdAt);
        }

        if ($postTypes)
        {
            $qb->andwhere($qb->expr()->in('i.type', $postTypes));
        }

        if ($offset)
        {
            $qb->setFirstResult($offset);
        }
        
        if ($limit)
        {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery();
        $objects = $query->getArrayResult();

        return $objects;
    }

    /*
     * Cancel an invite.
     * Set all users connected to this invite to their previous posts.
     *
     * @param integer $inviteId
     */
    public function cancelInvite($inviteId)
    {
        $invite = $this->findInviteBy($inviteId, null, null, null, true);
        if (!$invite)
        {
            return false;
        }

        // Loop through and re-assign posts to a users most recent post before this one
        foreach ($invite->getPosts() as $userPost)
        {
            $this->activateMostRecentPost($userPost->getCreatedBy()->getId(), false);

            $userPost->setStatus('Disabled');
            $this->em->persist($userPost);
        }

        $invite->setStatus('Cancelled');
        $this->em->persist($invite);

        $this->em->flush();

        return true;
    }

    public function activateMostRecentPost($userId, $andFlush=true)
    {
            $qb = $this->em->createQueryBuilder();
            $qb->select(array('p'))
               ->from('Whoot\WhootBundle\Entity\Post', 'p')
               ->where('p.status != :status AND p.createdBy = :user AND p.invite IS NULL')
               ->orderBy('p.createdAt', 'DESC')
               ->setMaxResults(1)
               ->setParameters(array(
                   'user'    => $userId,
                   'status'       => 'Active',
               ));

            $query = $qb->getQuery();

            $prevPost = $query->getResult(Query::HYDRATE_OBJECT);
            if (isset($prevPost[0]))
            {
                $prevPost = $prevPost[0];
                $prevPost->setStatus('Active');
                $this->em->persist($prevPost);

                if ($andFlush)
                {
                    $this->em->flush();
                }
            }
    }

    public function toggleAttending($userId, $myPost, $inviteId)
    {
        $result = array('status' => 'success');
        $myPost->setStatus('Disabled');

        // Decrement the attending on the old invite
        if ($myPost->getInvite())
        {
            $oldInvite = $this->findInviteBy($myPost->getInvite()->getId(), null, null, null, true);
            $oldInvite->incrementAttending(-1);
            $this->updateInvite($oldInvite, false);
        }

        // We are un-attending this invite
        if ($myPost->getInvite() && $myPost->getInvite()->getId() == $inviteId)
        {
            $this->activateMostRecentPost($userId);

            $result['newText'] = '+ I\'m Attending';
        }
        else
        {
            $invite = $this->findInviteBy($inviteId, null, null, null, true);

            $post = new Post();
            $post->setType($invite->getType());
            $post->setCreatedBy($myPost->getCreatedBy());
            $post->setInvite($invite);
            $this->em->persist($post);

            $invite->incrementAttending(1);
            $this->updateInvite($invite);

            $result['newText'] = '- Cancel Attending';
        }

        return $result;
    }
}
