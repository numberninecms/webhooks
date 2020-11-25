<?php

/*
 * This file is part of the NumberNine package.
 *
 * (c) William Arin <williamarin.dev@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class DeployCommand extends Command
{
    protected static $defaultName = 'app:numbernine:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update Docker files with registry image and run deploy script')
            ->addArgument(
                'docker-image',
                InputArgument::REQUIRED,
                'Docker image containing new file'
            )
            ->addArgument(
                'destination-volume',
                InputArgument::REQUIRED,
                'Destination volume containing files to replace'
            )
            ->addArgument(
                'app-path',
                InputArgument::REQUIRED,
                'Absolute path where the deploy script is located'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // @phpstan-ignore-next-line
        $dockerImage = (string)$input->getArgument('docker-image');
        // @phpstan-ignore-next-line
        $destinationVolume = (string)$input->getArgument('destination-volume');
        // @phpstan-ignore-next-line
        $appPath = (string)$input->getArgument('app-path');

        $mountedFolderName = '/srv/' . substr(md5(microtime()), 0, 6);

        $process = Process::fromShellCommandline(
            sprintf(
                'docker run --rm -i -v %s:%s %s rsync -aq --delete-after ./ %s/',
                $destinationVolume,
                $mountedFolderName,
                $dockerImage,
                $mountedFolderName
            )
        );

        $returnCode = $process->run(
            function (string $type, string $buffer) use ($output) {
                $output->write($buffer);
            }
        );

        if ($returnCode !== Command::SUCCESS) {
            return $returnCode;
        }

        if (!chdir($appPath)) {
            $io->error(sprintf('Unable to enter "%s" directory.', $appPath));
            return Command::FAILURE;
        }

        $process = Process::fromShellCommandline('make deploy');
        return $process->run(
            function (string $type, string $buffer) use ($output) {
                $output->write($buffer);
            }
        );
    }
}
