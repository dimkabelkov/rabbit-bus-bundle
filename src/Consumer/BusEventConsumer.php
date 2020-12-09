<?php

namespace Dimkabelkov\RabbitBusBundle\Consumer;

use Exception;
use JMS\Serializer\SerializerInterface;
use Dimkabelkov\RabbitBusBundle\BusEvent\BaseEvent;
use Dimkabelkov\RabbitBusBundle\Exception\RetryException;
use Dimkabelkov\RabbitBusBundle\Exception\UndefinedChannelException;
use Dimkabelkov\RabbitBusBundle\Map;
use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Dimkabelkov\RabbitBusBundle\Service\ProbeService;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BusEventConsumer implements ConsumerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected SerializerInterface $serializer;
    protected EventDispatcherInterface $eventDispatcher;
    protected BusService $busService;
    protected ProbeService $probeService;

    public function __construct(
        SerializerInterface $serializer,
        EventDispatcherInterface $eventDispatcher,
        BusService $busService,
        ProbeService $probeService
    )
    {
        $this->serializer      = $serializer;
        $this->eventDispatcher = $eventDispatcher;
        $this->busService      = $busService;
        $this->probeService    = $probeService;
    }

    /**
     * @param AMQPMessage $msg The message
     *
     * @return mixed false to reject and requeue, any other value to acknowledge
     *
     * @throws Exception
     */
    public function execute(AMQPMessage $msg)
    {
        $consumerTag = $msg->delivery_info['consumer_tag'];
        $eventExchangeName = $msg->get('exchange');
        $eventRoutingKey = $msg->get('routing_key');
        
        $payload = json_decode($msg->body, true);

        if (empty($payload['id']) || empty($payload['name'])) {
            $this->logger->error('Not found field id on name', [
                'body' => $msg->body
            ]);

            return true;
        }

        if (!empty($payload['exchange'])) {
            $eventExchangeName = $payload['exchange'];
        }

        $this->logger->info('Run handle bus event', [
            'event-id'       => $payload['id'],
            'event-name'     => $payload['name'],
            'queue-exchange' => $eventExchangeName,
            'queue-routing-key' => $eventRoutingKey
        ]);

        $status = false;

        try {

            $class = $this->busService->getEventByExchangeName($eventExchangeName);
            
            /** @var BaseEvent $event */
            $event = $this->serializer->fromArray($payload, $class);

            $event->setConsumerTag($consumerTag);
            $event->setRoutingKey($eventRoutingKey);

            $this->eventDispatcher->dispatch($event);

            $status = true;
        } catch (UndefinedChannelException $ex) {
            $this->logger->error('Undefined exchange', [
                'queue-exchange' => $eventExchangeName
            ]);
            $status = true;
        } catch (RetryException $ex) {
            $this->logger->error('Retry execute queue task', [
                'queue-exchange' => $eventExchangeName
            ]);
        } catch (Exception $ex) {
            $this->logger->error('Error execute queue task', [
                'queue-exchange' => $eventExchangeName,
                'error-message'  => $ex->getMessage()
            ]);
            throw $ex;
        }

        $this->logger->info('Complete queue task', [
            'queue-exchange' => $eventExchangeName
        ]);

        return $status;
    }
}
