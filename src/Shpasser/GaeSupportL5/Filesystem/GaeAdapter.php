<?php namespace Shpasser\GaeSupportL5\Filesystem;

use League\Flysystem\Adapter\Local;

/**
 * Class GaeAdapter
 *
 * The class overrides the existing method in order
 * to replace a call to 'reapath()' functions with
 * a call to 'gae_realpath()' function, which is
 * compatible with GAE buckets.
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

}