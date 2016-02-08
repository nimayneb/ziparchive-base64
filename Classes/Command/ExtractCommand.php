<?php namespace JBR\ZipArchive64;

/**********************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Jan Runte (jan.runte@hmmh.de), hmmh AG
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify it under
 *  the terms of the GNU General Public License as published by the
 *  Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 **********************************************************************/

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ExtractCommand
 */
class ExtractCommand extends Command {

	/**
	 * @param InputInterface  $input
	 * @param OutputInterface $output
	 *
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output) {
		$source = $input->getArgument('source');

		if ('/' !== $source{0}) {
			$source = getcwd() . $source;
		}

		if (false === is_file($source)) {
			$output->writeln(sprintf('Cannot extract from <%s>', $source));
			exit;
		}

		$target = $input->getArgument('target');

		if (true === empty($target)) {
			$target = getcwd();
		} elseif ('/' !== $target{0}) {
			$target = getcwd() . $target;
		}

		if (false === is_dir($target)) {
			$output->writeln(sprintf('Cannot extract to <%s>', $target));
			exit;
		}

		$zip = new ZipArchive64();
		if (false === $zip->open($source)) {
			$output->writeln(sprintf('Cannot open <%s>', $source));
			exit;
		}

		if (false === $zip->extractTo($target)) {
			$output->writeln(sprintf('Cannot extract to <%s>', $target));
			exit;
		}
	}

	/**
	 *
	 */
	protected function configure() {
		$this
            ->setName('extract')
            ->setDescription('Extract base64 encoded file and path names')
			->setDefinition([
				new InputArgument('source', InputArgument::REQUIRED, 'Source file'),
				new InputArgument('target', InputArgument::OPTIONAL, 'Target dir')
			])
        ;
	}
}