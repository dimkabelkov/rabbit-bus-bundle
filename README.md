# rabbit-bus-bundle

Multiple events listener fo SOA

```
Service A -> enevt -> rabbit-mq


rabbit-mq -> event -> Service B
rabbit-mq -> event -> Service C
rabbit-mq -> event -> Service ...
```


Add composer package `emag-tech-labs/rabbitmq-bundle` for `Symfony 5` or `php-amqplib/rabbitmq-bundle` for `Symfony 4`,
and add any version `symfony/monolog-bundle`

## Define env for app `A` nd `B`

```
APP_NAME=app
```

## Configuration and run

Config description

```
rabbit_bus
    event_classes: #array all events in app
    events
        multiple: #bool true - 1 consumer for all events, false - consumer for event 
        consumers: #array events for executed in app, defined for any field multiple
        producers: #array events for executed in app, for multiple = false
```

Add monolog channel

```
monolog:
    handlers:
        main:
            ...
            channels:
                - ...
                - '%env(string:APP_NAME)%.rabbit-bus'
                - ...
            ...
```

## Define event class for app `A` and `B` (define all event classes external repository, example: github.com/you-app/bus-events and include app `A` and `B`)

```
<?php

namespace YouProject\Event\ExamampleEvent;

use Dimkabelkov\RabbitBusBundle\BusEvent\AbstractEvent;

class ExamampleEvent extends AbstractEvent
{
    public const EXCHANGE = 'you-project.examample-event';
}

```

## Config example for `multi: true` app `A`

```
rabbit_bus
    events
        multiple: true
```

### Send event to rabbit

```
use YouProject\Event\ExamampleEvent;

// Any service

protected BusService $busService;

// -----
// -----
// -----
// -----

// any method() {
    $this->busService->publishBusEvent(new ExamampleEvent('event-name', 'event-id', 'event-value'));
// }
```

### Log app `A`

```
[2021-02-10T15:03:16.063755+03:00] app.rabbit-bus.INFO: Push event to rabbit-bus {"event-id":"event-id","event-name":"event-name","queue-exchange":"you-project.examample-event"} []
```

## Config example for `multi: true` app `B`

```
rabbit_bus
    event_classes:
        - YouProject\Event\ExamampleEvent
    events
        multiple: true
        consumers:
            - !php/const YouProject\Event\ExamampleEvent::EXCHANGE
```

### Define EventBusSubscriber app `B`

```
<?php

namespace App\EventListener;

use YouProject\Event\ExamampleEvent;
use Psr\Log\LoggerAwareInterface;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EventBusSubscriber implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ExamampleEvent::class => 'onExamampleEvent',
        ];
    }

    /**
     * @param ExamampleEvent $event
     *
     * @throws Exception
     */
    public function onExamampleEvent(ExamampleEvent $event)
    {
        $this->logger->info('Check handle event', $event->toArray());
    }
}
```

### Add EventBusSubscriber in app `B`

```
App\EventListener\EventBusSubscriber:
    tags:
        - { name: kernel.event_subscriber }
        - { name: monolog.logger, channel: '%env(string:APP_NAME)%.bus' }
```



### Run consumer app `B`

```
./bin/console rabbitmq:consumer rabbit-bus-events.multiple
```

### Log app `B`

```
[2021-02-10T15:03:16.076666+03:00] app.rabbit-bus.INFO: Run handle bus event {"event-id":"event-id","event-name":"event-name","queue-exchange":"ts-events.video.thumbnail-generate","queue-routing-key":"you-project.examample-event"} []
[2021-02-10T15:03:16.076862+03:00] app.rabbit-bus.INFO: Check handle event {...} []
[2021-02-10T15:03:16.076862+03:00] app.rabbit-bus.INFO: Complete queue task {"queue-exchange":"ts-events.video.thumbnail-generate"} []
```
