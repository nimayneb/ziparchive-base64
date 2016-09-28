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

use ZipArchive;

/**
 *
 */
class ZipArchive64 extends ZipArchive {

	/**
	 * @var string
	 */
	protected $currentFile;

	/**
	 * @param string $filename
	 * @param string $flags
	 *
	 * @return mixed
	 */
	public function open($filename, $flags = null) {
		$this->currentFile = $filename;

		return ErrorMessages::assert(parent::open(realpath($filename), $flags));
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	protected function getEncodedBase64($value) {
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
	protected function getDecodedBase64($value) {
		if (false !== strpos($value, '#')) {
			list($hash, $encodedName) = explode('#', $value);
			$name = base64_decode(str_replace(['-', '_'], ['+', '/'], $encodedName));

			if (intval($hash) === crc32($name)) {
				$value = $name;
			}
		}


		return $value;
	}

	/**
	 * @param string $reference
	 * @param string $method
	 *
	 * @return string
	 */
	protected function translateFilePath($reference, $method) {
		$paths = explode('/', $reference);
		$filePaths = [];

		foreach ($paths as $path) {
			$file = $this->{$method}($path);
			$filePaths[] = $file;
		}

		return implode('/', $filePaths);
	}

	/**
	 * @param string $reference
	 *
	 * @return string
	 */
	protected function getBase64EncodedFilePath($reference) {
		return $this->translateFilePath($reference, 'getEncodedBase64');
	}

	/**
	 * @param string $reference
	 *
	 * @return string
	 */
	protected function getBase64DecodedFilePath($reference) {
		return $this->translateFilePath($reference, 'getDecodedBase64');
	}

	/**
	 * @param string $dirname
	 *
	 * @return bool
	 */
	public function addEmptyDir($dirname) {
		return ErrorMessages::assert(parent::addEmptyDir($this->getBase64EncodedFilePath($dirname)));
	}

	/**
	 * @param string $localname
	 * @param string $contents
	 *
	 * @return bool
	 */
	public function addFromString($localname, $contents) {
		return ErrorMessages::assert(parent::addFromString($this->getBase64EncodedFilePath($localname), $contents));
	}

	/**
	 * @param string $filename
	 * @param string $localname
	 * @param int    $start
	 * @param int    $length
	 *
	 * @return bool
	 */
	public function addFile($filename, $localname = null, $start = 0, $length = 0) {
		if (null === $localname) {
			$localname = $filename;
		}

		return ErrorMessages::assert(
		    parent::addFile($filename, $this->getBase64EncodedFilePath($localname), $start, $length)
        );
	}

	/**
	 * @param string|array $entries
	 *
	 * @return void
	 */
	protected function fixEntries(&$entries) {
		if (true === is_array($entries)) {
			foreach ($entries as $key => $value) {
				$entries[$key] = $this->getBase64EncodedFilePath($value);
			}
		} elseif (true === is_string($entries)) {
			$entries = $this->getBase64EncodedFilePath($entries);
		}
	}

	/**
	 * @param $message
	 *
	 * @return bool
	 */
	protected function errorHandler($message) {
		error_log($message, 0);

		return false;
	}

	/**
	 * @param string $destination
	 * @param string|array $entries
	 *
	 * @return bool
	 */
	public function extractTo($destination, $entries = null) {
		$this->fixEntries($entries);

		$extractedFiles = [];

		for ($i = 0; $i < $this->numFiles; $i++) {
			$encodedFileName = $this->getNameIndex($i);

            $result = ErrorMessages::assert(parent::extractTo($destination, $encodedFileName));

			if (false === $result) {
				return $this->errorHandler(
				    sprintf('Unable to extract zip file <%s>!' . $this->currentFile)
                );
			}

			$extractedFiles[] = $encodedFileName;
		}

		$renamedFiles = [];

		foreach ($extractedFiles as $encodedFileName) {
			$decodedFileName = $this->getBase64DecodedFilePath($encodedFileName);
			$oldPaths = explode('/', $encodedFileName);
			$newPaths = explode('/', $decodedFileName);
			$checkPath = $destination;

			foreach ($oldPaths as $index => $path) {
				$newPath = $newPaths[$index];

				$resultOldName = sprintf('%s/%s', $checkPath, $path);
				$resultNewName = sprintf('%s/%s', $checkPath, $newPath);

				if (true === is_dir($resultOldName)) {
					if (false === rename($resultOldName, $resultNewName)) {
						return $this->errorHandler(
						    sprintf('Cannot rename directory <%s> to <%s>', $path, $newPaths[$index])
                        );
					}
				} elseif (true === is_file($resultOldName)) {
					if (true === is_file($resultNewName)) {
						if (false === unlink($resultNewName)) {
							return $this->errorHandler(
							    sprintf('Cannot overwrite file <%s>!', $newPaths[$index])
                            );
						}
					}

					if (false === rename($resultOldName, $resultNewName)) {
						return $this->errorHandler(
						    sprintf('Cannot rename file <%s> to <%s>', $path, $newPaths[$index])
                        );
					}
				}

				$checkPath = $resultNewName;
			}

			$renamedFiles[] = $checkPath;
		}

		if (count($extractedFiles) !== count($renamedFiles)) {
			return $this->errorHandler(
			    sprintf('Incompleted renaming files and paths from zip file <%s>!', $this->currentFile)
            );
		}

		return true;
	}

	/**
	 * @param string $name
	 * @param string $newname
	 *
	 * @return bool
	 */
	public function renameName($name, $newname) {
		return ErrorMessages::assert(
		    parent::renameName($this->getBase64EncodedFilePath($name), $this->getBase64EncodedFilePath($newname))
        );
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function deleteName($name) {
		return ErrorMessages::assert(parent::deleteName($this->getBase64EncodedFilePath($name)));
	}

	/**
	 * @param string $name
	 * @param int    $length
	 * @param string $flags
	 *
	 * @return string
	 */
	public function getFromName($name, $length = 0, $flags = null) {
		return ErrorMessages::assert(parent::getFromName($this->getBase64EncodedFilePath($name), $length, $flags));
	}

	/**
	 * @param string $name
	 * @param string $comment
	 *
	 * @return bool
	 */
	public function setCommentName($name, $comment) {
		return ErrorMessages::assert(parent::setCommentName($this->getBase64EncodedFilePath($name), $comment));
	}

	/**
	 * @param string $name
	 * @param string $flags
	 *
	 * @return string
	 */
	public function getCommentName($name, $flags = null) {
		return parent::getCommentName($this->getBase64EncodedFilePath($name), $flags);
	}

	/**
	 * @param int  $index
	 * @param string $flags
	 *
	 * @return array
	 */
	public function statIndex($index, $flags = null) {
		$result = parent::statIndex($index, $flags);
		$this->fixStats($result);

		return $result;
	}

	/**
	 * @param array $result
	 *
	 * @return void
	 */
	protected function fixStats(array &$result) {
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
	public function statName($name, $flags = null) {
		$result = parent::statName($this->getBase64EncodedFilePath($name), $flags);
		$this->fixStats($result);

		return $result;
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 */
	public function unchangeName($name) {
		return ErrorMessages::assert(parent::unchangeName($this->getBase64EncodedFilePath($name)));
	}
}