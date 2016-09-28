<?php
/**
 * Created by PhpStorm.
 * User: jan.runte
 * Date: 28.09.16
 * Time: 16:51
 */

namespace JBR\ZipArchive64;


use Exception;

class ErrorMessages
{
    /**
     * @var array
     */
    protected static $messages = [
        ZipArchive64::ER_OK => 'No error',
        ZipArchive64::ER_MULTIDISK => 'Multi-disk zip archives not supported',
        ZipArchive64::ER_RENAME => 'Renaming temporary file failed',
        ZipArchive64::ER_CLOSE => 'Closing zip archive failed',
        ZipArchive64::ER_SEEK => 'Seek error',
        ZipArchive64::ER_READ => 'Read error',
        ZipArchive64::ER_WRITE => 'Write error',
        ZipArchive64::ER_CRC => 'CRC error',
        ZipArchive64::ER_ZIPCLOSED => 'Containing zip archive was closed',
        ZipArchive64::ER_NOENT => 'No such file',
        ZipArchive64::ER_EXISTS => 'File already exists',
        ZipArchive64::ER_OPEN => 'Can\'t open file',
        ZipArchive64::ER_TMPOPEN => 'Failure to create temporary file',
        ZipArchive64::ER_ZLIB => 'Zlib error',
        ZipArchive64::ER_MEMORY => 'Malloc failure',
        ZipArchive64::ER_CHANGED => 'Entry has been changed',
        ZipArchive64::ER_COMPNOTSUPP => 'Compression method not supported',
        ZipArchive64::ER_EOF => 'Premature EOF',
        ZipArchive64::ER_INVAL => 'Invalid argument',
        ZipArchive64::ER_NOZIP => 'Not a zip archive',
        ZipArchive64::ER_INTERNAL => 'Internal error',
        ZipArchive64::ER_INCONS => 'Zip archive inconsistent',
        ZipArchive64::ER_REMOVE => 'Can\'t remove file',
        ZipArchive64::ER_DELETED => 'Entry has been deleted',
    ];

    /**
     * @param mixed $result
     *
     * @throws Exception
     * @return mixed
     */
    public static function assert($result)
    {
        if (true === is_int($result)) {
            throw new Exception(static::message($result));
        }

        return $result;
    }

    /**
     * @param integer $code
     * @return string
     */
    protected static function message($code)
    {
        $code = sprintf('An unknown error has occurred (%u)', $code);

        if (true === isset(static::$messages[$code])) {
            $code = static::$messages[$code];
        }

        return $code;
    }
}