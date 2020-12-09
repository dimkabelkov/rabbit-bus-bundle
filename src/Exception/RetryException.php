<?php

namespace Dimkabelkov\RabbitBusBundle\Exception;

use Throwable;

class RetryException extends Exception
{
    public function __construct(Throwable $previous)
    {
        parent::__construct($previous->getMessage(), $previous->getCode(), $previous);
    }
}
