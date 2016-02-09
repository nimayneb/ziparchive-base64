<?php

/**
 *
 * @param string $file
 *
 * @return bool
 */
function includeFileIfExists($file) {
    return (true === file_exists($file)) ? include_once $file : false;
}

if (false === ($autoload = includeFileIfExists(sprintf('%s/../vendor/autoload.php', __DIR__)))) {
    echo "Please install composer dependencies first!\n\n";
    exit(1);
}

return $autoload;