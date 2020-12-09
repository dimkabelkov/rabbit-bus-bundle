<?php

namespace Dimkabelkov\RabbitBusBundle\Exception;

use Throwable;

class UndefinedChannelException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct('Undefined exchange: ' . $message, $code, $previous);
    }
}
