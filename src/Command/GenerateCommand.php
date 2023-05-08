<?php

namespace Crayoon\HyperfGrpcClient\Command;

use Hyperf\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class GenerateCommand extends Command
{

    protected bool $coroutine = false;
    protected ?string $name = "gen:grpc";

    public function configure()
    {
        parent::configure();
        $this->setDescription('Generate Grpc And Client');
        $this->addArgument('protobuf', InputArgument::REQUIRED, 'The protobuf file');
        $this->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'The output dir.');
        $this->addOption('path', 'i', InputOption::VALUE_OPTIONAL, 'The proto path.');
    }

    public function handle()
    {
        $protobuf = $this->input->getArgument('protobuf');
        $output = $this->input->getOption('output') ?: getcwd();
        $path = $this->input->getOption('path') ?: dirname($protobuf);

        $process = new Process([
            __DIR__ . '/../../grpc-generator',
            '-protoPath=' . $path,
            '-proto=' . $protobuf
        ]);

        $process->run(function ($type, $buffer) {
            if (!$this->output->isVerbose() || !$buffer) {
                return;
            }

            $this->output->writeln($buffer);
        });

        $return = $process->getExitCode();
        $result = $process->getOutput();

        if ($return === 0) {
            $this->output->writeln('<info>PHP classes successfully generate.</info>');
            $this->output->writeln($result);
            return $return;
        }

        $this->output->writeln('<error>protoc exited with an error (' . $return . ') when executed with: </error>');
        $this->output->writeln('');
        $this->output->writeln('  ' . $process->getCommandLine());
        $this->output->writeln('');
        $this->output->writeln($result);
        $this->output->writeln('');
        $this->output->writeln($process->getErrorOutput());
        $this->output->writeln('');

        return $return;
    }
}