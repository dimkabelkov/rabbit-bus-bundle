<?php

namespace Dimkabelkov\RabbitBusBundle\Command;

use Exception;
use Dimkabelkov\RabbitBusBundle\BusEvent\SampleEvent;
use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class BusEmitEventCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const COMMAND_NAME = 'rabbit-bus:emit:event';

    protected static $defaultName = self::COMMAND_NAME;

    /** @var BusService */
    private BusService $busService;

    /**
     * UserCrmSyncCommand constructor.
     *
     * @param BusService $busService
     */
    public function __construct(BusService $busService)
    {
        parent::__construct(null);

        $this->busService = $busService;
    }

    protected function configure()
    {
        $this->setDescription('Emit bus event')
             ->addArgument('exchange', InputArgument::OPTIONAL, 'Channel name', SampleEvent::EXCHANGE)
             ->addArgument('name', InputArgument::OPTIONAL, 'Name', 'some-event')
             ->addArgument('id', InputArgument::OPTIONAL, 'Object id', 'some-id')
             ->addArgument('value', InputArgument::OPTIONAL, 'Value', 'some-value')
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return mixed|void
     *
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $exchange = $input->getArgument('exchange');
        $id      = $input->getArgument('id');
        $name    = $input->getArgument('name');
        $value   = $input->getArgument('value');

        $event = $this->busService->createEvent($exchange, $name, $id, $value);

        $io = new SymfonyStyle($input, $output);
        $io->title('Emitting event, exchange: ' . $exchange);
        $io->writeln(print_r($event->toArray(), true));

        $this->busService->dispatchBusEvent($event);
    }

}
