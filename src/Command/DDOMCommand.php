<?php

namespace App\Command;

use App\Services\DdomService;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class DDOMCommand extends Command
{
    public function __construct(
      protected DdomService $runtimeService
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('ddom:runtime')
            ->setDescription('Check current Ip and update OVH DNS if needed')
            ->setHelp('Check current Ip and update OVH DNS if needed');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Check current Ip and update OVH DNS if needed');

        try {
            $this->runtimeService->run($io);
        } catch (Exception|TransportExceptionInterface $e) {
            $io->error($e->getMessage());

            return 1;
        }

        return 0;
    }
}
