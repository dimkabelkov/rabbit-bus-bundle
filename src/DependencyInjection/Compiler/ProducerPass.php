<?php

namespace Dimkabelkov\RabbitBusBundle\DependencyInjection\Compiler;

use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ProducerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container): void
    {
        if (!$container->has(BusService::class)) {
            return;
        }

        $producers = $container->findTaggedServiceIds('old_sound_rabbit_mq.producer');
        $busService = $container->findDefinition(BusService::class);

        foreach ($producers as $key => $producer) {
            if (empty($producer[0]['eventName'])) {
                continue;
            }

            $busService->addMethodCall('addProducer', [
                new Reference($key),
                $producer[0]['eventName']
            ]);
        }
    }
}
