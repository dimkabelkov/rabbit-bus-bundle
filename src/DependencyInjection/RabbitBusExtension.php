<?php

namespace Dimkabelkov\RabbitBusBundle\DependencyInjection;

use Dimkabelkov\RabbitBusBundle\Command\BusEmitEventCommand;
use Dimkabelkov\RabbitBusBundle\Command\BusEmitProbeCommand;
use Dimkabelkov\RabbitBusBundle\Consumer\BusEventConsumer;
use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Dimkabelkov\RabbitBusBundle\Service\ProbeService;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class RabbitBusExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $processor     = new Processor();
        $configuration = new Configuration();

        $config = $processor->processConfiguration($configuration, $configs);

        $multiple = !empty($config['events']['multiple']);
        $consumers = array_unique($config['events']['consumers'] ?? []);
        $producers = array_unique($config['events']['producers'] ?? []);

        $container->setParameter('rabbit_bus.multiple', $multiple);
        $container->setParameter('rabbit_bus.consumers', $consumers);
        $container->setParameter('rabbit_bus.event_classes', !empty($config['event_classes']) ? $config['event_classes'] : []);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('services.yaml');


        $routingKeys = $consumers;
        
        if ($multiple) {
            $consumers = [
                BusService::EXCHANGE_MULTIPLE_NAME
            ];
        }
        
        foreach ($routingKeys as $routingKey) {
            $routingKeys[] = $routingKey . '-' . gethostname();
        }

        // consumers
        foreach ($consumers as $eventName) {
            
            if (!$routingKeys) {
                break;
            }
            
            $definition = new Definition('%old_sound_rabbit_mq.consumer.class%');
            $definition->setPublic(true);
            $definition->addTag('old_sound_rabbit_mq.base_amqp');
            $definition->addTag('old_sound_rabbit_mq.consumer');

            $definition->addMethodCall('setExchangeOptions', [[
                'name' => $eventName,
                'type' => 'fanout'
            ]]);
            
            $definition->addMethodCall('setQueueOptions', [[
//                'name' => $eventName,
                'auto_delete' => true,
                'routing_keys' => array_merge($routingKeys ?? [], [
                    gethostname()
                ])
            ]]);

            $definition->addMethodCall('setCallback', [[new Reference(BusEventConsumer::class), 'execute']]);

            $definition->addArgument(new Reference(sprintf('old_sound_rabbit_mq.connection.%s', 'default')));

            $name = sprintf('old_sound_rabbit_mq.%s_consumer', $eventName);
            $container->setDefinition($name, $definition);

            if (!$container->has(BusEventConsumer::class)) {
                return;
            }

            $callbackDefinition = $container->findDefinition(BusEventConsumer::class);

            $refClass = new \ReflectionClass($callbackDefinition->getClass() ?? BusEventConsumer::class);
            if ($refClass->implementsInterface('OldSound\RabbitMqBundle\RabbitMq\DequeuerAwareInterface')) {
                $callbackDefinition->addMethodCall('setDequeuer', array(new Reference($name)));
            }
        }
        
        if ($multiple) {
            $producers = [
                BusService::EXCHANGE_MULTIPLE_NAME
            ];
        }
        
        // producers
        foreach ($producers as $eventName) {
            $definition = new Definition('%old_sound_rabbit_mq.producer.class%');
            $definition->setPublic(true);
            $definition->addTag('old_sound_rabbit_mq.base_amqp');
            $definition->addTag('old_sound_rabbit_mq.producer', [
                'eventName' => $eventName
            ]);

            $definition->addMethodCall('setExchangeOptions', [[
                'name' => $eventName,
                'type' => 'fanout',
            ]]);

            $definition->addMethodCall('setQueueOptions', [[
                'auto_delete' => true,
                'arguments' => [
                    'x-message-ttl' => ['I', 5000],
                    'x-expires' => ['I', 5000]
                ]
            ]]);

            $definition->addArgument(new Reference(sprintf('old_sound_rabbit_mq.connection.%s', 'default')));

            $producerServiceName = sprintf('old_sound_rabbit_mq.%s_producer', $eventName);
            $container->setDefinition($producerServiceName, $definition);
        }
    }
}