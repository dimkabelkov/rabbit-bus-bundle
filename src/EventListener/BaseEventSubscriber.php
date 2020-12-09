<?php

namespace Dimkabelkov\RabbitBusBundle\EventListener;

use Dimkabelkov\RabbitBusBundle\Event\PublishEvent;
use Dimkabelkov\RabbitBusBundle\Lib\Environment;
use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class BaseEventSubscriber implements EventSubscriberInterface
{
    /** @var PublishEvent[] */
    private static $publishEvents = [];

    /** @var BusService */
    protected $busService;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            PublishEvent::class      => 'onPublishEvent',
            KernelEvents::TERMINATE  => 'onTerminate',
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function __construct(BusService $busService)
    {
        $this->busService = $busService;
    }

    /**
     * @param PublishEvent $publishEvent
     */
    public function onPublishEvent(PublishEvent $publishEvent)
    {
        if (self::isCli()) {
            $this->handlePublishEvent($publishEvent);
        } else {
            self::$publishEvents[] = $publishEvent;
        }
    }

    public function onTerminate()
    {
        foreach (self::$publishEvents as $index => $event) {
            $this->handlePublishEvent($event);
        }
        self::$publishEvents = [];
    }

    protected function handlePublishEvent(PublishEvent $publishEvent)
    {
        $this->busService->publishBusEvent($publishEvent->getBusEvent());
    }
    
    /**
     * Detects if the current script is running in a command-line environment.
     *
     * @see https://www.drupal.org/files/issues/is-cli.patch
     * @see https://stackoverflow.com/questions/933367/php-how-to-best-determine-if-the-current-invocation-is-from-cli-or-web-server/25967493#25967493
     * @return bool
     */
    public static function isCli()
    {
        if (defined('STDIN')) {
            return true;
        }

        if (in_array(PHP_SAPI, array('cli', 'cli-server', 'phpdbg'))) {
            return true;
        }

        if (empty($_SERVER['REMOTE_ADDR']) && !isset($_SERVER['HTTP_USER_AGENT']) && count($_SERVER['argv']) > 0) {
            return true;
        }

        if (!array_key_exists('REQUEST_METHOD', $_SERVER)) {
            return true;
        }

        return false;
    }
}
