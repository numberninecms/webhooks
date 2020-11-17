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
use Symfony\Component\Process\Process;

class NumbernineUpdateCommand extends Command
{
    protected static $defaultName = 'app:numbernine:update';

    protected function configure(): void
    {
        $this
            ->setDescription('Update NumberNine Docker files with registry image')
            ->addArgument(
                'docker-image',
                InputArgument::REQUIRED,
                'Docker image containing new file'
            )
            ->addArgument(
                'destination-volume',
                InputArgument::REQUIRED,
                'Destination volume containing files to replace'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // @phpstan-ignore-next-line
        $dockerImage = (string)$input->getArgument('docker-image');
        // @phpstan-ignore-next-line
        $destinationVolume = (string)$input->getArgument('destination-volume');

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

        return $process->run(
            function (string $type, string $buffer) use ($output) {
                $output->write($buffer);
            }
        );
    }
}
