<?php

namespace Shpasser\GaeSupportL5\Queue;

use Illuminate\Queue\QueueServiceProvider as LaravelQueueServiceProvider;

class QueueServiceProvider extends LaravelQueueServiceProvider
{
    /**
     * Register the connectors on the queue manager.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    public function registerConnectors($manager)
    {
        parent::registerConnectors($manager);
        $this->registerGaeConnector($manager);
    }

    /**
     * Register the GAE queue connector.
     *
     * @param  \Illuminate\Queue\QueueManager  $manager
     * @return void
     */
    protected function registerGaeConnector($manager)
    {
        $app = $this->app;

        $manager->addConnector('gae', function () use ($app) {
            return new GaeConnector($app['encrypter'], $app['request']);
        });
    }

    /**
     * Register the GAE compatible queue listener.
     *
     * @return void
     */
    protected function registerListener()
    {
        $this->registerListenCommand();

        $this->app->singleton('queue.listener', function ($app) {
            return new Listener($app->basePath());
        });
    }

}
