<?php

namespace Dimkabelkov\RabbitBusBundle\Command;

use Exception;
use Dimkabelkov\RabbitBusBundle\BusEvent\AbstractEvent;
use Dimkabelkov\RabbitBusBundle\Service\BusService;
use Dimkabelkov\RabbitBusBundle\Service\ProbeService;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BusEmitProbeCommand extends Command implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const COMMAND_NAME = 'rabbit-bus:emit:probe';

    protected static $defaultName = self::COMMAND_NAME;

    private BusService $busService;
    private ProbeService $probeService;
    
    /**
     * UserCrmSyncCommand constructor.
     *
     * @param BusService   $busService
     * @param ProbeService $probeService
     */
    public function __construct(
        BusService $busService,
        ProbeService $probeService
    )
    {
        $this->busService       = $busService;
        $this->probeService     = $probeService;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setDescription('Emit bus probe')
             ->addArgument('probe', InputArgument::REQUIRED, sprintf('Probe type [%s]', implode(', ', AbstractEvent::PROBES)))
             ->addArgument('exchange', InputArgument::REQUIRED, sprintf('Exchange name [%s]', implode(', ', $this->busService->getConsumers())))
             ->addOption('timeout', 't', InputOption::VALUE_REQUIRED, 'Timeout seconds', 15)
             ->addOption('frequency', 'f', InputOption::VALUE_REQUIRED, 'Check frequency seconds', 3);
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
        $probe     = $input->getArgument('probe');
        $exchange  = $input->getArgument('exchange');
        
        $timeout   = $input->getOption('timeout');
        $frequency = $input->getOption('frequency');
        
        $event = $this->busService->createProbe($exchange, $probe);

        $this->logger->info('Check probe', [
            'probe' => $probe,
            'exchange' => $exchange
        ]);

        $this->busService->publishBusEvent($event, $exchange . '-' . gethostname());

        $checked = $this->checkOrTimeout(function () use ($event) {
            return $this->probeService->isProbeFixed($event);
        }, $timeout, $frequency);

        if ($checked) {
            $this->logger->info('Probe successful', [
                'probe' => $probe,
                'exchange' => $exchange
            ]);
        } else {
            $this->logger->info('Probe failed', [
                'probe' => $probe,
                'exchange' => $exchange
            ]);
        }

        if (!$checked) {
            return 1;
        }

        return 0;
    }

    protected function checkOrTimeout(callable $check, int $timeout, int $frequency)
    {
        $start  = time();
        $finish = $timeout + $start;

        $checked = false;
        while (time() < $finish) {
            if ($checked = $check()) break;

            sleep($frequency);
        }

        return $checked;
    }
}
