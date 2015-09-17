<?php

namespace Shpasser\GaeSupportL5\Foundation;

use Illuminate\Foundation\Application as IlluminateApplication;
use Shpasser\GaeSupportL5\Storage\Optimizer;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;

class Application extends IlluminateApplication
{
    /**
     * AppIdentityService class instantiation is done using the class
     * name string so we can first check if the class exists and only then
     * instantiate it.
     */
    const GAE_ID_SERVICE = 'google\appengine\api\app_identity\AppIdentityService';

    /**
     * The GAE app ID.
     *
     * @var string
     */
    protected $appId;

    /**
     * 'true' if running on GAE.
     * @var boolean
     */
    protected $runningOnGae;

    /**
     * GAE storage bucket path.
     * @var string
     */
    protected $gaeBucketPath;


    /**
     * GAE storage optimizer
     */
    protected $optimizer = null;

    /**
     * Create a new GAE supported application instance.
     *
     * @param string $basePath
     */
    public function __construct($basePath = null)
    {
        $this->gaeBucketPath = null;

        // Load the 'realpath()' function replacement
        // for GAE storage buckets.
        require_once(__DIR__ . '/gae_realpath.php');

        $this->detectGae();

        if ($this->isRunningOnGae()) {
            $this->replaceDefaultSymfonyLineDumpers();
        }

        $this->optimizer = new Optimizer($basePath, $this->runningInConsole());
        $this->optimizer->bootstrap();

        parent::__construct($basePath);
    }


    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        $path = $this->optimizer->getCachedConfigPath();

        return $path ?: parent::getCachedConfigPath();
    }


    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        $path = $this->optimizer->getCachedRoutesPath();

        return $path ?: parent::getCachedRoutesPath();
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        $path = $this->optimizer->getCachedServicesPath();

        if ($path) {
            return $path;
        }

        if ($this->isRunningOnGae()) {
            return $this->storagePath().'/framework/services.json';
        }

        return parent::getCachedServicesPath();
    }


    /**
     * Detect if the application is running on GAE.
     */
    protected function detectGae()
    {
        if (! class_exists(self::GAE_ID_SERVICE)) {
            $this->runningOnGae = false;
            $this->appId = null;

            return;
        }

        $AppIdentityService = self::GAE_ID_SERVICE;
        $this->appId = $AppIdentityService::getApplicationId();
        $this->runningOnGae = ! preg_match('/dev~/', getenv('APPLICATION_ID'));
    }

    /**
     * Replaces the default output stream of Symfony's
     * CliDumper and HtmlDumper classes in order to
     * be able to run on Google App Engine.
     *
     * 'php://stdout' is used by CliDumper,
     * 'php://output' is used by HtmlDumper,
     * both are not supported on GAE.
     */
    protected function replaceDefaultSymfonyLineDumpers()
    {
        HtmlDumper::$defaultOutput =
        CliDumper::$defaultOutput =
            function ($line, $depth, $indentPad) {
                if (-1 !== $depth) {
                    echo str_repeat($indentPad, $depth).$line.PHP_EOL;
                }
            };
    }

    /**
     * Returns 'true' if running on GAE.
     *
     * @return bool
     */
    public function isRunningOnGae()
    {
        return $this->runningOnGae;
    }

    /**
     * Returns the GAE app ID.
     *
     * @return string
     */
    public function getGaeAppId()
    {
        return $this->appId;
    }

    /**
     * Override the storage path
     *
     * @return string Storage path URL
     */
    public function storagePath()
    {
        if ($this->runningOnGae) {
            if (! is_null($this->gaeBucketPath)) {
                return $this->gaeBucketPath;
            }

            $buckets = ini_get('google_app_engine.allow_include_gs_buckets');
            // Get the first bucket in the list.
            $bucket = current(explode(', ', $buckets));

            if ($bucket) {
                $this->gaeBucketPath = "gs://{$bucket}/storage";

                if (env('GAE_SKIP_GCS_INIT')) {
                    return $this->gaeBucketPath;
                }

                if (! file_exists($this->gaeBucketPath)) {
                    mkdir($this->gaeBucketPath);
                    mkdir($this->gaeBucketPath.'/app');
                    mkdir($this->gaeBucketPath.'/framework');
                    mkdir($this->gaeBucketPath.'/framework/views');
                }

                return $this->gaeBucketPath;
            }
        }

        return parent::storagePath();
    }
}
