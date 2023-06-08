<?php
declare(strict_types=1);
/**
 * @author   crayxn <https://github.com/crayxn>
 * @contact  crayxn@qq.com
 */

namespace Crayoon\HyperfGrpcClient\Command;

use Hyperf\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;

class GrpcGenerateCommand extends Command
{
    protected bool $coroutine = false;
    protected ?string $name = "gen:grpc";

    public function configure()
    {
        parent::configure();
        $this->setDescription('Generate Grpc And Client');
        $this->addArgument('protobuf', InputArgument::IS_ARRAY, 'The protobuf file');
        $this->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'The output dir.');
        $this->addOption('paths', 'i', InputOption::VALUE_OPTIONAL, 'The proto paths.');
        $this->addOption('plugin', 'p', InputOption::VALUE_OPTIONAL, 'The plugin path');
    }

    public function handle()
    {
        $protobuf = implode(",", $this->input->getArgument('protobuf'));
        $output = $this->input->getOption('output') ?: getcwd();
        $paths = $this->input->getOption('paths') ?: '../protos/';
        $plugin_path = $this->input->getOption('plugin') ?: "/usr/local/lib/grpc_php_plugin";

        $process = new Process([
            __DIR__ . '/../../bin/grpc-generator',
            '-protoPath=' . $paths,
            '-pluginPath=' . $plugin_path,
            '-proto=' . $protobuf,
            '-path=' . $output
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
            $this->output->writeln('');
            $this->output->writeln($result);
            $this->output->writeln('');
            $this->output->writeln('<info>Successfully generate.</info>');
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