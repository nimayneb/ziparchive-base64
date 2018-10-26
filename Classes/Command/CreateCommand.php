<?php namespace JBR\ZipArchive64\Command;

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions  of source code must retain the above copyright notice,  this
 * list of conditions and the following disclaimer.
 *
 * 2. Redistributions  in  binary  form  must  reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 *
 * THIS  SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY  EXPRESS OR IMPLIED WARRANTIES,  INCLUDING, BUT NOT LIMITED TO, THE  IMPLIED
 * WARRANTIES  OF  MERCHANTABILITY  AND   FITNESS  FOR  A  PARTICULAR  PURPOSE  ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY  DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL  DAMAGES
 * (INCLUDING,  BUT  NOT LIMITED TO,  PROCUREMENT OF SUBSTITUTE GOODS  OR  SERVICES;
 * LOSS  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND  ON
 * ANY  THEORY  OF  LIABILITY,  WHETHER  IN  CONTRACT,  STRICT  LIABILITY,  OR TORT
 * (INCLUDING  NEGLIGENCE OR OTHERWISE)  ARISING IN ANY WAY OUT OF THE USE OF  THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 ************************************************************************************/

use DirectoryIterator;
use Iterator;
use JBR\ZipArchive64\ZipArchive64;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CreateCommand
 *
 * @package JBR\ZipArchive64
 */
class CreateCommand extends Command
{

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \JBR\ZipArchive64\InvalidArgumentException
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output):void
    {
        $source = $input->getArgument('source');

        if ('/' !== $source{0}) {
            $source = getcwd() . '/' . $source;
        }

        if (false === is_dir($source)) {
            $output->writeln(sprintf('Cannot create from <%s>', $source));
            exit;
        }

        $target = $input->getArgument('target');

        if (true === empty($target)) {
            $target = getcwd();
        } elseif ('/' !== $target{0}) {
            $target = getcwd() . '/' . $target;
        }

        if (false === is_dir(dirname($target))) {
            $output->writeln(sprintf('Cannot create archive to <%s>', dirname($target)));
            exit;
        }

        $recursive = $input->getOption('recursive');
        $directory = $this->getDirectoryIterator($source, (false === empty($recursive)));

        $zip = new ZipArchive64(
            function($output) {
                echo "{$output}\n";
            },

            function($error) {
                file_put_contents('php://stderr', "{$error}\n");
            }
        );

        $update = $input->getOption('update');

        if (false === $zip->open($target, $this->getArchiveMode($update))) {
            $output->writeln(sprintf('Cannot open <%s>', $source));
            exit;
        }

        $verbose = $input->getOption('verbose');
        $i = 0;

        foreach ($directory as $fileInfo) {
            if (true === $fileInfo->isDir()) {
                continue;
            }

            $i++;
            $file = $fileInfo->getRealPath();
            $truncate = $input->getOption('truncate');
            $localName = $this->getLocalName($file, (false === empty($truncate)));

            if (false === empty($verbose)) {
                $output->writeln(sprintf('<%s> Add "%s"', str_pad($i, 4, '0', STR_PAD_LEFT), $localName));
            }

            if (false === $zip->addFile($file, $localName)) {
                $output->writeln(sprintf('Cannot add source file <%s>', $file));
                exit;
            }
        }

        $zip->close();
    }

    /**
     * @param boolean $update
     *
     * @return integer
     */
    private function getArchiveMode($update):int
    {
        $option = ZipArchive64::CREATE;

        if (true === $update) {
            $option = ZipArchive64::EXCL;
        }

        return $option;
    }

    /**
     * @param string $source
     * @param bool $recursive
     *
     * @return SplFileInfo[]|Iterator
     */
    protected function getDirectoryIterator($source, $recursive = false)
    {
        if (true === $recursive) {
            $directory = new RecursiveDirectoryIterator($source);

            return new RecursiveIteratorIterator($directory);
        }

        return new DirectoryIterator($source);
    }

    /**
     * @param string $file
     * @param boolean $truncate
     *
     * @return string
     */
    protected function getLocalName($file, $truncate = false): string
    {
        if (true === $truncate) {
            $file = str_replace(getcwd(), '', $file);
        }

        return $file;
    }

    /**
     *
     */
    protected function configure(): void
    {
        $this
            ->setName('create')
            ->setDescription('Create archive with base64 encoded file and path names')
            ->setDefinition([
                new InputArgument(
                    'source', InputArgument::REQUIRED,
                    'Specifies the source path which you like to pack.'
                ),
                new InputArgument(
                    'target', InputArgument::OPTIONAL,
                    'Specifies the target archive file in which all files are to be packed.'
                ),
                new InputOption(
                    'update', 'u', InputOption::VALUE_NONE,
                    'Update existing or add new files into archive'
                ),
                new InputOption(
                    'recursive', 'r', InputOption::VALUE_NONE,
                    'Recursively find files in source directory'
                ),
                new InputOption(
                    'truncate', 't', InputOption::VALUE_NONE,
                    'Truncate current working directory from archive'
                )
            ]);
    }
}