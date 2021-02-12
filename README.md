# rabbit-bus-bundle

Надо вручную добавить `emag-tech-labs/rabbitmq-bundle` для Symfony 5 или `php-amqplib/rabbitmq-bundle` для Symfony 4,
и для любой версии добавить `symfony/monolog-bundle`

```
APP_NAME=app
```

## Описание класса события

```
<?php

namespace YouProject\Event\ExamampleEvent;

use Dimkabelkov\RabbitBusBundle\BusEvent\AbstractEvent;

class ExamampleEvent extends AbstractEvent
{
    public const EXCHANGE = 'you-project.examample-event';
}

```

## Конфигурация и запуск

Описания общего конфига

```
rabbit_bus
    event_classes: array #массив всех событий которые будут использоваться в сервисе
    events
        multiple: bool #режим работе, true - 1 консьюмера на все события, false - каждый для своего 
        consumers: array #массив очередей которые будут обрабатываться данным сервисом, заполняетя вне зависимости от поля multiple 
        producers: array #массив продюсеров которые будут использваться в случает multiple = false\ 
```

Пример конфига и кода для работы в режиме `multi: true` для сервиса с продюсером

```
rabbit_bus
    events
        multiple: true
```

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

Вывод в лог

```
[2021-02-10T15:03:16.063755+03:00] app.rabbit-bus.INFO: Push event to rabbit-bus {"event-id":"event-id","event-name":"event-name","queue-exchange":"you-project.examample-event"} []
```

Пример для работы в режиме `multi: true` для сервиса с консьюмером

```
rabbit_bus
    event_classes:
        - YouProject\Event\ExamampleEvent
    events
        multiple: true
        consumers:
            - !php/const YouProject\Event\ExamampleEvent::EXCHANGE
```

Добавляем свой подписчик на событие
```
App\EventListener\EventBusSubscriber:
    tags:
        - { name: kernel.event_subscriber }
        - { name: monolog.logger, channel: '%env(string:APP_NAME)%.bus' }
```

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
    public function onFileEvent(ExamampleEvent $event)
    {
        $this->logger->info('Check handle event', $event->toArray());
    }
}
```

Run consumer

```
./bin/console rabbitmq:consumer rabbit-bus-events.multiple
```

Вывод в лог

```
[2021-02-10T15:03:16.076666+03:00] app.rabbit-bus.INFO: Run handle bus event {"event-id":"event-id","event-name":"event-name","queue-exchange":"ts-events.video.thumbnail-generate","queue-routing-key":"you-project.examample-event"} []
[2021-02-10T15:03:16.076862+03:00] app.rabbit-bus.INFO: Check handle event {...} []
[2021-02-10T15:03:16.076862+03:00] app.rabbit-bus.INFO: Complete queue task {"queue-exchange":"ts-events.video.thumbnail-generate"} []
```

