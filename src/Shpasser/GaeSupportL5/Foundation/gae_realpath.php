<?php

/**
 * GAE replacement for the original realpath() function.
 */
function gae_realpath($path)
{
    $result = realpath($path);
    if ($result == false) {
        if (file_exists($path)) {
            $result = $path;
        }
    }

    return $result;
}
