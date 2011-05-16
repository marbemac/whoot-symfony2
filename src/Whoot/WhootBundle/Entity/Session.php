<?php

namespace Whoot\WhootBundle\Entity;

/**
 * @orm:Entity
 * @orm:Table(name="session")
 */
class Session
{
    /**
     * @var integer $sessionId
     * @orm:Id
     * @orm:Column(type="string", length="255", name="session_id")
     */
    protected $sessionId;

    /**
     * @var string $sessionValue
     * @orm:Column(type="text", name="session_value")
     */
    protected $sessionValue;

    /**
     * @var string $sessionTime
     * @orm:Column(type="integer", length="11", name="session_time")
     */
    protected $sessionTime;
}