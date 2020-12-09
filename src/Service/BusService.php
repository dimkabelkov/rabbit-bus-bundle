<?php

namespace Dimkabelkov\RabbitBusBundle\Service;

use Dimkabelkov\RabbitBusBundle\BusEvent\BaseEvent;
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
    use LoggerAwareTrait;

    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var Producer[] */
    private $producers = [];

    /** @var string */
    private string $exchangeMultipleName;

    /** @var array */
    private array $exchangeToClass;

    /** @var bool */
    private bool $multiple = false;

    /** @var array */
    private array $consumers = [];

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        string $exchangeMultipleName,
        array $exchangeToClass = [],
        bool $multiple = false,
        array $consumers = []
    )
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->exchangeMultipleName = $exchangeMultipleName;
        $this->exchangeToClass = $exchangeToClass;

        $this->exchangeToClass[BusEvent\SampleEvent::EXCHANGE] = BusEvent\SampleEvent::class;

        $this->multiple = $multiple;
        $this->consumers = $consumers;
    }

    /**
     * @param BaseEvent $busEvent
     */
    public function dispatchBusEvent(BaseEvent $busEvent)
    {
        $this->eventDispatcher->dispatch(new PublishEvent($busEvent));
    }

    /**
     * @param BaseEvent $busEvent
     * @param string|null $routingKey
     */
    public function publishBusEvent(BaseEvent $busEvent, ?string $routingKey = null)
    {
        $eventExchangeName = $busEvent::EXCHANGE;

        if ($this->multiple) {
            $producer = $this->producers[$this->exchangeMultipleName];
        } else {
            $producer = $this->producers[$eventExchangeName];
        }

        $event = $busEvent->toArray();
        $event['exchange'] = $eventExchangeName;

        $this->logger->info('Push event to rabbit-bus', [
            'event-id' => $busEvent->id,
            'event-name' => $busEvent->name,
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
        if (empty($this->exchangeToClass[$exchange])) {
            throw new UndefinedChannelException($exchange);
        }

        return $this->exchangeToClass[$exchange];
    }

    /**
     * @param      $exchange
     * @param      $name
     * @param      $id
     * @param null $value
     *
     * @return BaseEvent
     *
     * @throws UndefinedChannelException
     */
    public function createEvent($exchange, $name, $id, $value = null): BaseEvent
    {
        $class = $this->getEventByExchangeName($exchange);
        return new $class($name, $id, $value);
    }

    /**
     * @param $exchange
     * @param $probe
     *
     * @return BaseEvent
     * @throws UndefinedChannelException
     */
    public function createProbe($exchange, $probe): BaseEvent
    {
        $class = $this->getEventByExchangeName($exchange);
        
        /** @var BaseEvent $event */
        $event = new $class();

        return $event->makeProbe($probe);
    }
}
