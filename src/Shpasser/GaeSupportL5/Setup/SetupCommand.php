<?php namespace Shpasser\GaeSupportL5\Setup;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class SetupCommand extends Command {

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
		$configurator = new Configurator($this);
		$configurator->configure(
			$this->argument('app-id'),
			$this->option('config'),
			$this->option('bucket'));
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
			array('config', null, InputOption::VALUE_NONE, 'Generate "app.yaml" and "php.ini" config files.', null),
			array('bucket', null, InputOption::VALUE_REQUIRED, 'Use the specified gs-bucket instead of the default one.', null),
		);
	}

}
