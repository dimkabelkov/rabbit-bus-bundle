<?php

namespace Dimkabelkov\RabbitBusBundle\BusEvent;

class SampleEvent extends BaseEvent
{
    public const EXCHANGE = 'rabbit-bus-events.sample';
}
