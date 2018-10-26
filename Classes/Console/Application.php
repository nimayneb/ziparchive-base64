<?php namespace JBR\ZipArchive64\Console;

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

use JBR\ZipArchive64\Command\CreateCommand;
use JBR\ZipArchive64\Command\ExtractCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * Class Application
 *
 * @package JBR\ZipArchive64
 */
class Application extends BaseApplication
{

    /**
     * @param string $name
     * @param string $version
     */
    public function __construct(string $name = 'UNKNOWN', string $version = 'UNKNOWN')
    {
        $composer = json_decode(file_get_contents(__DIR__ . '/../../composer.json'), true);
        parent::__construct('zip64', $composer['version']);
    }

    /**
     * @return \Symfony\Component\Console\Command\Command[]
     */
    protected function getDefaultCommands(): array
    {
        $commands = parent::getDefaultCommands();
        $commands[] = new CreateCommand();
        $commands[] = new ExtractCommand();

        return $commands;
    }
}