<?php namespace Shpasser\GaeSupportL5;

use Illuminate\Support\ServiceProvider;
use Shpasser\GaeSupportL5\Setup\SetupCommand;
use Shpasser\GaeSupportL5\Filesystem\GaeAdapter as GaeFileSystemAdapter;
use Storage;

class GaeSupportServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('gae', function($app)
        {
            return Storage::repository(new GaeFilesystemAdapter);
        });
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['gae.setup'] = $this->app->share(function($app)
		{
			return new SetupCommand;
		});

		$this->commands('gae.setup');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('gae-support');
	}

}
