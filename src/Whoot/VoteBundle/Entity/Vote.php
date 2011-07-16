<?php

namespace Whoot\VoteBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
 * @ORM\Table(name="vote")
 * @ORM\HasLifecycleCallbacks
 */
class Vote
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var integer $amount
     * @ORM\Column(type="integer")
     */
    protected $amount;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @var dateTime $updatedAt
     * @ORM\Column(type="datetime", name="updated_at", nullable=true)
     */
    protected $updatedAt;

    /**
     * @var dateTime $createdAt
     * @ORM\Column(type="datetime", name="created_at", nullable=true)
     */
    protected $createdAt;

    /**
     * @var integer $voter
     * @ORM\ManyToOne(targetEntity="Whoot\WhootUserBundle\Entity\User")
     * @ORM\JoinColumn(name="voter_id", referencedColumnName="id")
     */
    protected $voter;

    /**
     * @var integer $affectedUser
     * @ORM\ManyToOne(targetEntity="Whoot\WhootUserBundle\Entity\User")
     * @ORM\JoinColumn(name="affected_user_id", referencedColumnName="id")
     */
    protected $affectedUser;

    /**
     * @var integer $post
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Post")
     * @ORM\JoinColumn(name="post_id", referencedColumnName="id")
     */
    protected $post;

    /**
     * @var integer $invite
     * @ORM\ManyToOne(targetEntity="Whoot\WhootBundle\Entity\Invite")
     * @ORM\JoinColumn(name="invite_id", referencedColumnName="id")
     */
    protected $invite;

    /**
     * @var string $status
     * @ORM\Column(type="string")
     */
    protected $type;

    public function  __construct()
    {
        $this->setStatus('Active');
    }

    /**
     * Get id
     *
     * @return integer $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * Get amount
     *
     * @return integer $amount
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set status
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * Get status
     *
     * @return status $status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set type
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * Get type
     *
     * @return status $type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set voter
     *
     * @param Whoot\WhootUserBundle\Entity\User $voter
     */
    public function setVoter($voter)
    {
        $this->voter = $voter;
    }

    /**
     * Get voter
     *
     * @return Whoot\WhootUserBundle\Entity\User $voter
     */
    public function getVoter()
    {
        return $this->voter;
    }

    /**
     * Set affectedUser
     *
     * @param Whoot\WhootUserBundle\Entity\User $affectedUser
     */
    public function setAffectedUser($affectedUser)
    {
        $this->affectedUser = $affectedUser;
    }

    /**
     * Get affectedUser
     *
     * @return Whoot\WhootUserBundle\Entity\User $affectedUser
     */
    public function getAffectedUser()
    {
        return $this->affectedUser;
    }

    /**
     * Get post
     *
     * @return Whoot\WhootBundle\Entity\Post $post
     */
    public function getPost()
    {
        return $this->post;
    }

    /**
     * Set post
     *
     * @return Whoot\WhootBundle\Entity\Post $post
     */
    public function setPost($post)
    {
        $this->post = $post;
    }

    /**
     * Get invite
     *
     * @return Whoot\WhootBundle\Entity\Invite $invite
     */
    public function getInvite()
    {
        return $this->invite;
    }

    /**
     * Set invite
     *
     * @return Whoot\WhootBundle\Entity\Invite $invite
     */
    public function setInvite($invite)
    {
        $this->invite = $invite;
    }

    /**
     * @ORM\prePersist
     */
    public function touchCreated()
    {
        $this->createdAt = $this->updatedAt = new \DateTime();
    }

    /**
     * @ORM\preUpdate
     */
    public function touchUpdated()
    {
        $this->updatedAt = new \DateTime();
    }
}