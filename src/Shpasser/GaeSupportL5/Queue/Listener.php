<?php

namespace Shpasser\GaeSupportL5\Queue;

use Illuminate\Queue\Listener as IlluminateQueueListener;
use Symfony\Component\Process\PhpExecutableFinder;

/**
 * Queue Listener class overriding the original one in order to
 * prevent execution of PHP function not supported by GAE, and
 * in particular 'escapeshellarg()'.
 */
class Listener extends IlluminateQueueListener
{
    /**
     * Build the environment specific worker command.
     *
     * @return string
     */
    protected function buildWorkerCommand()
    {
        if (! app()->isRunningOnGae()) {
            return parent::buildWorkerCommand();
        }

        $binary = (new PhpExecutableFinder)->find(false);

        if (defined('HHVM_VERSION')) {
            $binary .= ' --php';
        }

        if (defined('ARTISAN_BINARY')) {
            $artisan = ARTISAN_BINARY;
        } else {
            $artisan = 'artisan';
        }

        $command = 'queue:work %s --queue=%s --delay=%s --memory=%s --sleep=%s --tries=%s';

        return "{$binary} {$artisan} {$command}";
    }
}
