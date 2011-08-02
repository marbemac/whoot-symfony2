<?php

namespace Whoot\WhootBundle\Model;

interface ObjectInterface
{
    function getStatus();

    function setStatus($status);
}