<?php namespace JBR\ZipArchive64\Command;

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistributionv and use in source and binary forms, with or without modification,
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

use JBR\ZipArchive64\ZipArchive64;
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
class CreateCommand extends Command {

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
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

		if (false === is_dir($target)) {
			$output->writeln(sprintf('Cannot extract to <%s>', $target));
			exit;
		}

		$zip = new ZipArchive64();
		if (false === $zip->open($source, ZipArchive64::OVERWRITE)) {
			$output->writeln(sprintf('Cannot open <%s>', $source));
			exit;
		}

		foreach ($files as $file) {
			$truncate = $input->getArgument('truncate-cwd');
			if (false === $zip->addFile($file, $this->getLocalName($file, (false === empty($truncate))))) {
				$output->writeln(sprintf('Cannot add source file <%s>', $file));
				exit;
			}
		}

		$zip->close();
	}

	/**
	 * @param string $file
	 * @param boolean $input
	 *
	 * @return string
	 */
	protected function getLocalName($file, $truncate = false) {
		if (true === $truncate) {
			$file = str_replace(getcwd(), '', $file);
		}

		return $file;
	}


	/**
	 *
	 */
	protected function configure() {
		$this
			->setName('create')
			->setDescription('Create archive with base64 encoded file and path names')
			->setDefinition([
				new InputArgument(
					'source', InputArgument::REQUIRED, 'Specifies the source path which you like to pack.'
				),
				new InputArgument(
					'target', InputArgument::OPTIONAL, 'Specifies the target file that you want as packed archive'
				),
				new InputOption(
					'recursive', 'r', InputOption::VALUE_OPTIONAL, 'Recursively compress files in directory', false
				),
				new InputOption(
					'truncate-cwd', 't',  InputOption::VALUE_OPTIONAL, 'Truncate current working directory from archive', false
				)
			])
        ;
	}
}