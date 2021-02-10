<?php

namespace Dimkabelkov\RabbitBusBundle\Service;

use Dimkabelkov\RabbitBusBundle\BusEvent\AbstractEvent;
use Dimkabelkov\RabbitBusBundle\Event\PublishEvent;
use Dimkabelkov\RabbitBusBundle\Exception\UndefinedChannelException;
use OldSound\RabbitMqBundle\RabbitMq\Producer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Dimkabelkov\RabbitBusBundle\BusEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Сервис для работы с Crm
 */
class BusService implements LoggerAwareInterface
{
    public const EXCHANGE_MULTIPLE_NAME = 'rabbit-bus-events.multiple';

    use LoggerAwareTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Producer[] */
    private $producers = [];

    /** @var array */
    private array $eventClasses;

    /** @var bool */
    private bool $multiple = false;

    /** @var array */
    private array $consumers = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        array $eventClasses = [],
        bool $multiple = false,
        array $consumers = []
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->eventClasses = [];

        foreach ($eventClasses as $eventClass) {
            if (in_array(AbstractEvent::class, class_parents($eventClass))) {
                $this->eventClasses[$eventClass::EXCHANGE] = $eventClass;
            }
        }
        
        $this->multiple = $multiple;
        $this->consumers = $consumers;
    }

    /**
     * @param AbstractEvent $abstractEvent
     */
    public function dispatchBusEvent(AbstractEvent $abstractEvent)
    {
        $this->eventDispatcher->dispatch(new PublishEvent($abstractEvent));
    }

    /**
     * @param AbstractEvent $abstractEvent
     * @param string|null $routingKey
     */
    public function publishBusEvent(AbstractEvent $abstractEvent, ?string $routingKey = null)
    {
        $eventExchangeName = $abstractEvent::EXCHANGE;

        if ($this->multiple) {
            $producer = $this->producers[self::EXCHANGE_MULTIPLE_NAME];
        } else {
            $producer = $this->producers[$eventExchangeName];
        }

        $event = $abstractEvent->toArray();
        $event['exchange'] = $eventExchangeName;

        $this->logger->info('Push event to rabbit-bus', [
            'event-id' => $abstractEvent->id,
            'event-name' => $abstractEvent->name,
            'queue-exchange' => $eventExchangeName
        ]);

        $producer->publish(json_encode($event), $routingKey ?: $eventExchangeName);
    }

    /**
     * @param Producer $producer
     * @param string   $eventName
     */
    public function addProducer(Producer $producer, string $eventName)
    {
        $this->producers[$eventName] = $producer;
    }

    /**
     */
    public function getConsumers()
    {
        return $this->consumers;
    }

    /**
     * @param string $exchange
     *
     * @return string
     *
     * @throws UndefinedChannelException
     */
    public function getEventByExchangeName(string $exchange): string
    {
        if (empty($this->eventClasses[$exchange])) {
            throw new UndefinedChannelException($exchange);
        }

        return $this->eventClasses[$exchange];
    }

    /**
     * @param      $exchange
     * @param      $name
     * @param      $id
     * @param null $value
     *
     * @return AbstractEvent
     *
     * @throws UndefinedChannelException
     */
    public function createEvent($exchange, $name, $id, $value = null): AbstractEvent
    {
        $class = $this->getEventByExchangeName($exchange);
        return new $class($name, $id, $value);
    }

    /**
     * @param $exchange
     * @param $probe
     *
     * @return AbstractEvent
     * @throws UndefinedChannelException
     */
    public function createProbe($exchange, $probe): AbstractEvent
    {
        $class = $this->getEventByExchangeName($exchange);
        
        /** @var AbstractEvent $abstractEvent */
        $abstractEvent = new $class();

        return $abstractEvent->makeProbe($probe);
    }
}
