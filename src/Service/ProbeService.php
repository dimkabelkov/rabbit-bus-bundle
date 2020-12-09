<?php

namespace Dimkabelkov\RabbitBusBundle\Service;

use Dimkabelkov\RabbitBusBundle\BusEvent\BaseEvent;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ProbeService implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
    }

    public function fixProbe(BaseEvent $event)
    {
        if (!$event->isProbe()) {
            throw new \InvalidArgumentException(sprintf('Not a probe event [%s]', $event->name));
        }

        $probeFilename = $this->probeFilename($event);
        $dirname       = dirname($probeFilename);
        if (!file_exists($dirname)) {
            mkdir($dirname, 0777, true);
        }

        touch($probeFilename);

        if (!file_exists($probeFilename)) {
            throw new \RuntimeException(sprintf('Failed to fix probe [%s:%s]', $event->name, $event->id));
        }
    }

    public function isProbeFixed(BaseEvent $event): bool
    {
        if (!$event->isProbe()) {
            throw new \InvalidArgumentException(sprintf('Not a probe event [%s]', $event->name));
        }

        $file = $this->probeFilename($event);
        
        return file_exists($file);
    }

    protected function probeFilename(BaseEvent $event)
    {
        return $this->probeDir($event) . DIRECTORY_SEPARATOR . $event->id;
    }

    protected function probeDir(BaseEvent $event)
    {
        $eventName   = str_replace('Dimkabelkov\\RabbitBusBundle\\BusEvent\\', '', get_class($event));
        $eventName   = str_replace('\\', '-', strtolower($eventName));

        $cachePath = implode(DIRECTORY_SEPARATOR, [$this->cacheDir, 'rabbit-bus', 'probes', $eventName]);

        return $cachePath;
    }
}
