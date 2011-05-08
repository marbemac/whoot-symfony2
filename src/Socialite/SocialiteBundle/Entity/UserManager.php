<?php

namespace Socialite\SocialiteBundle\Entity;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;

use FOS\UserBundle\Entity\UserManager as BaseUserManager;

class UserManager extends BaseUserManager
{
    protected $em;

    /**
     * Constructor.
     *
     * @param EntityManager           $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }
}
