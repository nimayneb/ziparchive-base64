<?php

/**
 *
 * @param string $file
 *
 * @return bool|mixed
 */
function includeFileIfExists(string $file)
{
    if (true === file_exists($file)) {
        return include_once $file;
    } else {
        return false;
    }
}

if (false === ($autoload = includeFileIfExists(sprintf('%s/vendor/autoload.php', __DIR__)))) {
    echo "No autoload file found. Please install composer dependencies first!\n\n";
    exit(1);
}

return $autoload;
