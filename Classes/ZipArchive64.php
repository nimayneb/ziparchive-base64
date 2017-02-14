<?php namespace JBR\ZipArchive64;

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

use Closure;
use ZipArchive;

/**
 * @method errorHandler(string $message) : bool
 * @method outputHandler(string $message) : void
 */
class ZipArchive64 extends ZipArchive
{

    /**
     * @var string
     */
    protected $currentFile;

    /**
     * @var Closure
     */
    protected $outputHandler = null;

    /**
     * @var Closure
     */
    protected $errorHandler = null;

    /**
     * @param Closure $outputHandler
     * @param Closure $errorHandler
     */
    public function __construct(Closure $outputHandler = null, Closure $errorHandler = null)
    {
        $this->outputHandler = $outputHandler;
        $this->errorHandler = $errorHandler;
    }

    /**
     * @param string $filename
     * @param string $flags
     *
     * @throws InvalidArgumentException
     * @return mixed
     */
    public function open($filename, $flags = null)
    {
        $this->currentFile = $filename;

        $path = realpath(dirname($filename));
        if ((true === empty($path)) || (false === is_dir($path))) {
            throw new InvalidArgumentException(sprintf('Cannot create zip file in a none directory <%s>', dirname($filename)));
        }

        $filename = sprintf('%s/%s', $path, basename($filename));

        return ErrorMessages::assert($filename, parent::open($filename, $flags));
    }

    /**
     * @param string $dirname
     *
     * @return bool
     */
    public function addEmptyDir($dirname)
    {
        return ErrorMessages::assert($dirname, parent::addEmptyDir($this->getBase64EncodedFilePath($dirname)));
    }

    /**
     * @param string $reference
     *
     * @return string
     */
    protected function getBase64EncodedFilePath($reference)
    {
        return $this->translateFilePath($reference, 'getEncodedBase64');
    }

    /**
     * @param string $reference
     * @param string $method
     *
     * @return string
     */
    protected function translateFilePath($reference, $method)
    {
        $paths = explode('/', $reference);
        $filePaths = [];

        foreach ($paths as $path) {
            $file = $this->{$method}($path);
            $filePaths[] = $file;
        }

        return implode('/', $filePaths);
    }

    /**
     * @param string $localname
     * @param string $contents
     *
     * @return bool
     */
    public function addFromString($localname, $contents)
    {
        return ErrorMessages::assert($localname, parent::addFromString($this->getBase64EncodedFilePath($localname), $contents));
    }

    /**
     * @param string $filename
     * @param string $localname
     * @param int $start
     * @param int $length
     *
     * @return bool
     */
    public function addFile($filename, $localname = null, $start = 0, $length = 0)
    {
        if (null === $localname) {
            $localname = $filename;
        }

        return ErrorMessages::assert(
            $filename,
            parent::addFile($filename, $this->getBase64EncodedFilePath($localname), $start, $length)
        );
    }

    /**
     * @param string $destination
     *
     * @return array
     * @throws InvalidAccessException
     * @throws InvalidArchiveException
     */
    protected function extractFiles($destination)
    {
        $extractedFiles = [];

        for ($i = 0; $i < $this->numFiles; $i++) {
            $encodedFileName = $this->getNameIndex($i);

            $result = ErrorMessages::assert($encodedFileName, parent::extractTo($destination, $encodedFileName));
            $this->output(sprintf('Extracting file <%s>...', $encodedFileName));

            if (false === $result) {
                throw new InvalidArchiveException(
                    sprintf('Unable to extract zip file <%s>!', $this->currentFile)
                );
            }

            $extractedFiles[] = $encodedFileName;
        }

        return $extractedFiles;
    }

    /**
     * @param string $checkFilePath
     * @param array $oldPaths
     * @param array $newPaths
     *
     * @return bool|string
     * @throws InvalidAccessException
     * @throws InvalidArchiveException
     */
    protected function fixFolders($checkFilePath, array $oldPaths, array $newPaths)
    {
        foreach ($oldPaths as $index => $path) {
            $newPath = $newPaths[$index];

            $resultOldName = str_replace('\\', '/', sprintf('%s/%s', $checkFilePath, $path));
            $resultNewName = str_replace('\\', '/', sprintf('%s/%s', $checkFilePath, $newPath));

            if (true === is_dir($resultOldName)) {
                if ('..' === $resultNewName) {
                    throw new InvalidArchiveException(
                        'Broken ZipArchive! You cannot rename to <parent path> (symbolic link).'
                    );
                }

                if ('.' === $resultNewName) {
                    throw new InvalidArchiveException(
                        'Broken ZipArchive! You cannot rename to <current path> (symbolic link).'
                    );
                }

                if (false === rename($resultOldName, $resultNewName)) {
                    throw new InvalidAccessException(
                        sprintf('Cannot rename directory <%s> to <%s>', $path, $newPaths[$index])
                    );
                }
            } elseif (true === is_file($resultOldName)) {
                if ((true === is_file($resultNewName)) && (false === unlink($resultNewName))) {
                    throw new InvalidAccessException(
                        sprintf('Cannot overwrite file <%s>!', $newPaths[$index])
                    );
                }

                if (false === rename($resultOldName, $resultNewName)) {
                    throw new InvalidAccessException(
                        sprintf('Cannot rename file <%s> to <%s>', $path, $newPaths[$index])
                    );
                }
            }

            $checkFilePath = $resultNewName;
        }

        return $checkFilePath;
    }

    /**
     * @param string $destination
     * @param array $extractedFiles
     *
     * @return array
     * @throws InvalidAccessException
     */
    protected function renameFiles($destination, array $extractedFiles)
    {
        $renamedFiles = [];

        foreach ($extractedFiles as $encodedFileName) {
            $decodedFileName = $this->getBase64DecodedFilePath($encodedFileName);

            $oldPaths = explode('/', $encodedFileName);
            $newPaths = explode('/', $decodedFileName);
            $checkFilePath = $destination;

            $this->output(sprintf('Renaming to <%s>...', $decodedFileName));

            $renamedFiles[] = $fixedFile = $this->fixFolders($checkFilePath, $oldPaths, $newPaths);

            $info = $this->statName($decodedFileName);

            if ((false === empty($info['mtime'])) && (false === filemtime($fixedFile))) {
                throw new InvalidAccessException(
                    sprintf('Cannot set modified date of file <%s>', $info['name'])
                );
            }
        }

        return $renamedFiles;
    }

    /**
     * @param string $destination
     * @param string|array $entries
     * @return bool
     * @throws InvalidArchiveException
     */
    public function extractTo($destination, $entries = null)
    {
        $this->fixEntries($entries);
        $extractedFiles = $this->extractFiles($destination);
        $renamedFiles = $this->renameFiles($destination, $extractedFiles);

        if (count($extractedFiles) !== count($renamedFiles)) {
            throw new InvalidArchiveException(
                sprintf('Incompleted renaming files and paths from zip file <%s>!', $this->currentFile)
            );
        }

        return true;
    }

    /**
     * @param string $output
     *
     * @return void
     */
    protected function output($output)
    {
        if (null !== $this->outputHandler) {
            $this->outputHandler($output);
        }
    }

    /**
     * @param string|array $entries
     *
     * @return void
     */
    protected function fixEntries(&$entries)
    {
        if (true === is_array($entries)) {
            foreach ($entries as $key => $value) {
                $entries[$key] = $this->getBase64EncodedFilePath($value);
            }
        } elseif (true === is_string($entries)) {
            $entries = $this->getBase64EncodedFilePath($entries);
        }
    }

    /**
     * @param string $error
     *
     * @return void
     */
    protected function error($error)
    {
        if (null !== $this->errorHandler) {
            $this->errorHandler($error);
        }
    }

    /**
     * @param string $reference
     *
     * @return string
     */
    protected function getBase64DecodedFilePath($reference)
    {
        return $this->translateFilePath($reference, 'getDecodedBase64');
    }

    /**
     * @param string $name
     * @param string $newname
     *
     * @return bool
     */
    public function renameName($name, $newname)
    {
        return ErrorMessages::assert(
            $name,
            parent::renameName($this->getBase64EncodedFilePath($name), $this->getBase64EncodedFilePath($newname))
        );
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function deleteName($name)
    {
        return ErrorMessages::assert($name, parent::deleteName($this->getBase64EncodedFilePath($name)));
    }

    /**
     * @param string $name
     * @param int $length
     * @param string $flags
     *
     * @return string
     */
    public function getFromName($name, $length = 0, $flags = null)
    {
        return ErrorMessages::assert($name, parent::getFromName($this->getBase64EncodedFilePath($name), $length, $flags));
    }

    /**
     * @param string $name
     * @param string $comment
     *
     * @return bool
     */
    public function setCommentName($name, $comment)
    {
        return ErrorMessages::assert($name, parent::setCommentName($this->getBase64EncodedFilePath($name), $comment));
    }

    /**
     * @param string $name
     * @param string $flags
     *
     * @return string
     */
    public function getCommentName($name, $flags = null)
    {
        return parent::getCommentName($this->getBase64EncodedFilePath($name), $flags);
    }

    /**
     * @param int $index
     * @param string $flags
     *
     * @return array
     */
    public function statIndex($index, $flags = null)
    {
        $result = parent::statIndex($index, $flags);
        $this->fixStats($result);

        return $result;
    }

    /**
     * @param array $result
     *
     * @return void
     */
    protected function fixStats(array &$result)
    {
        if (true === isset($result['name'])) {
            $result['name'] = $this->getBase64EncodedFilePath($result['name']);
        }
    }

    /**
     * @param string $name
     * @param string $flags
     *
     * @return array
     */
    public function statName($name, $flags = null)
    {
        $result = parent::statName($this->getBase64EncodedFilePath($name), $flags);
        $this->fixStats($result);

        return $result;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return ErrorMessages::assert($this->currentFile, parent::close());
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function unchangeName($name)
    {
        return ErrorMessages::assert($name, parent::unchangeName($this->getBase64EncodedFilePath($name)));
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function getEncodedBase64($value)
    {
        return sprintf(
            '%s#%s',
            crc32($value),
            str_replace(['+', '/'], ['-', '_'], base64_encode($value))
        );
    }

    /**
     * @param string $value
     *
     * @return string
     */
    protected function getDecodedBase64($value)
    {
        if (false !== strpos($value, '#')) {
            list($hash, $encodedName) = explode('#', $value);
            $name = base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedName));

            if (intval($hash) === crc32($name)) {
                $value = $name;
            }
        }

        return $value;
    }
}