<?php

namespace Whoot\NotificationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;
    
use Evario\NotificationBundle\Entity\Notification as BaseNotification;
use Evario\NotificationBundle\Entity\NotificationInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @ORM\Entity
 * @ORM\Table(name="notification")
 * @ORM\HasLifecycleCallbacks
 */
class Notification extends BaseNotification implements NotificationInterface
{
    /**
     * @var integer $id
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\generatedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var User $affectedUser
     * @ORM\ManyToOne(targetEntity="Whoot\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="affected_user_id", referencedColumnName="id")
     */
    protected $affectedUser;

    public function __construct() {
    }

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set affectedUser
     *
     * @param Whoot\UserBundle\Entity\User $affectedUser
     */
    public function setAffectedUser($affectedUser)
    {
        $this->affectedUser = $affectedUser;
    }

    /**
     * Get createdBy
     *
     * @return Whoot\UserBundle\Entity\User
     */
    public function getAffectedUser()
    {
        return $this->affectedUser;
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