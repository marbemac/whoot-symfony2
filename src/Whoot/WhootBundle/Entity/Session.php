<?php

namespace Whoot\WhootBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
    
/**
 * @ORM\Entity
 * @ORM\Table(name="session")
 */
class Session
{
    /**
     * @var integer $sessionId
     * @ORM\Id
     * @ORM\Column(type="string", length="255", name="session_id")
     */
    protected $sessionId;

    /**
     * @var string $sessionValue
     * @ORM\Column(type="text", name="session_value")
     */
    protected $sessionValue;

    /**
     * @var string $sessionTime
     * @ORM\Column(type="integer", length="11", name="session_time")
     */
    protected $sessionTime;
}