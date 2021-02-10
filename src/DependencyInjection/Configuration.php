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
                ->arrayNode('event_classes')
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
