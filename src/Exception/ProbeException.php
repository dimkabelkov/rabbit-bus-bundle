<?php

namespace Dimkabelkov\RabbitBusBundle\Exception;

use Dimkabelkov\RabbitBusBundle\BusEvent\BaseEvent;
use Throwable;

class ProbeException extends Exception
{
    protected BaseEvent $probe;

    public function __construct(BaseEvent $probe, $message = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct(
            sprintf('Probe [%s:%s] failed: %s',
                $probe->getProbeName(), $probe->id, $message)
            , $code, $previous);
    }

    /**
     * @return BaseEvent
     */
    public function getProbe(): BaseEvent
    {
        return $this->probe;
    }
}
