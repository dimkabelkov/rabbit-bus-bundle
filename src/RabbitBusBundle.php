<?php

namespace Dimkabelkov\RabbitBusBundle;

use Dimkabelkov\RabbitBusBundle\DependencyInjection\Compiler\ProducerPass;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RabbitBusBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new ProducerPass());
    }
}
