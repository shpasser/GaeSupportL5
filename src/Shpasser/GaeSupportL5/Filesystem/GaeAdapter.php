<?php namespace Shpasser\GaeSupportL5\Filesystem;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;


/**
 * Class GaeAdapter
 *
 * The class overrides the existing methods in order to:
 *
 * - 'ensureDirectory()' replace a call to 'reapath()' functions with
 * a call to 'gae_realpath()' function, which is
 * compatible with GCS buckets,
 *
 * - 'writeStream()' replace 'fopen()' mode from 'w+', which is not supported
 * on GCS buckets and replaces it with 'w', as for the
 * specific function both 'w+' and 'w' shuold work peoperly.
 *
 * @package Shpasser\GaeSupportL5\Filesystem
 */
class GaeAdapter extends Local {

    /**
     * {@inheritdoc}
     */
    protected function ensureDirectory($root)
    {
        if (is_dir($root) === false) {
            mkdir($root, 0755, true);
        }

        return gae_realpath($root);
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config)
    {
        $location = $this->applyPathPrefix($path);
        $this->ensureDirectory(dirname($location));

        if (! $stream = fopen($location, 'w')) {
            return false;
        }

        while (! feof($resource)) {
            fwrite($stream, fread($resource, 1024), 1024);
        }

        if (! fclose($stream)) {
            return false;
        }

        if ($visibility = $config->get('visibility')) {
            $this->setVisibility($path, $visibility);
        }

        return compact('path', 'visibility');
    }

}
