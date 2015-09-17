<?php

namespace Shpasser\GaeSupportL5\Setup;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SetupCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'gae:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Google App Engine support to the application.';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        $dbSocket = $this->option('db-socket');
        $dbHost   = $this->option('db-host');
        $dbName   = $this->option('db-name');

        if (! is_null($dbName) && (is_null($dbSocket) && is_null($dbHost))) {
            $this->error("Option '--db-name' requires at least one of: '--db-socket' OR '--db-host' to be defined.");
            return;
        }

        $configurator = new Configurator($this);
        $configurator->configure(
            $this->argument('app-id'),
            $this->option('config'),
            $this->option('cache-config'),
            $this->option('bucket'),
            $this->option('db-socket'),
            $this->option('db-name'),
            $this->option('db-host')
        );
    }

    /**
     * Get the console command arguments.
     *
     * @return array
     */
    protected function getArguments()
    {
        return array(
            array('app-id', InputArgument::REQUIRED, 'GAE application ID.'),
        );
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return array(
            array('config', null, InputOption::VALUE_NONE,
                  'Generate "app.yaml" and "php.ini" config files.', null),
            array('cache-config', null, InputOption::VALUE_NONE,
                'Generate cached Laravel config file for use on Google App Engine.', null),
            array('bucket', null, InputOption::VALUE_REQUIRED,
                  'Use the specified gs-bucket instead of the default one.', null),
            array('db-socket', null, InputOption::VALUE_REQUIRED,
                  'Cloud SQL socket connection string for production environment.', null),
            array('db-name', null, InputOption::VALUE_REQUIRED,
                  'Cloud SQL database name.', null),
            array('db-host', null, InputOption::VALUE_REQUIRED,
                  'Cloud SQL database host IPv4 address for local environment.', null),
        );
    }
}
