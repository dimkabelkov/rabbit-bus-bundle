<?php


namespace Dimkabelkov\RabbitBusBundle\Event;

use Dimkabelkov\RabbitBusBundle\BusEvent\BaseEvent;
use Symfony\Contracts\EventDispatcher\Event;

class PublishEvent extends Event
{
    /** @var BaseEvent  */
    protected $busEvent;

    public function __construct(BaseEvent $baseEvent)
    {
        $this->busEvent = $baseEvent;
    }

    public function getBusEvent(): BaseEvent
    {
        return $this->busEvent;
    }
}
