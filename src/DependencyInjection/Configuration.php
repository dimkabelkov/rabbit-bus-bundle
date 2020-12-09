<?php

namespace Dimkabelkov\RabbitBusBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * Сервис загрузки файла
 *
 * Непосредственно отсуществяет загрузку файла в хранилище
*/
class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('rabbit_bus');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('exchange_multiple_name')->defaultValue('rabbit-bus-events.multiple')->end()
                ->arrayNode('exchange_to_class')
                ->useAttributeAsKey('name')
                    ->scalarPrototype()
                    ->end()
                ->end()
                ->arrayNode('events')
                    ->children()
                        ->booleanNode('multiple')
                            ->defaultFalse()
                        ->end()
                        ->arrayNode('consumers')
                            ->scalarPrototype()
                            ->end()
                        ->end()
                        ->arrayNode('producers')
                            ->scalarPrototype()
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
