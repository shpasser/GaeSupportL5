<?php namespace Shpasser\GaeSupportL5\Foundation;

use Illuminate\Foundation\Application as IlluminateApplication;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Filesystem\Filesystem;
use Shpasser\GaeSupportL5\Storage\CacheFs;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\VarDumper\Dumper\CliDumper;


class Application extends IlluminateApplication {

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

        if ($this->isRunningOnGae())
        {
            $this->replaceDefaultSymfonyLineDumpers();
            $this->initializeCacheFs($basePath);
        }

        parent::__construct($basePath);
    }

    /**
     * Initializes the Cache Filesystem.
     *
     * @param string $basePath
     */
    protected function initializeCacheFs($basePath)
    {
        CacheFs::register();
        mkdir('cachefs://framework');
        mkdir('cachefs://framework/views');
        mkdir('cachefs://bootstrap');
        mkdir('cachefs://bootstrap/cache');

        if (env('GAE_CACHE_CONFIG_FILE') === true)
        {
            $this->cacheFile(
                $basePath.'/bootstrap/cache/config.php',
                'cachefs://bootstrap/cache/config.php');
        }

        if(env('GAE_CACHE_ROUTES_FILE') === true)
        {
            $this->cacheFile(
                $basePath.'/bootstrap/cache/routes.php',
                'cachefs://bootstrap/cache/routes.php');
        }
    }

    /**
     * Adds the requested file to cache.
     *
     * @param string $path path to the file to be cached.
     * @param string $cachefsPath path for the cached file(under 'cachefs://').
     */
    protected function cacheFile($path, $cachefsPath)
    {
        if (file_exists($path))
        {
            $contents = file_get_contents($path);
            file_put_contents($cachefsPath, $contents);
        }
    }


    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        if ($this->isRunningOnGae() && env('GAE_CACHE_CONFIG_FILE') === true)
        {
            return 'cachefs://bootstrap/cache/config.php';
        }

        return parent::getCachedConfigPath();
    }


    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        if ($this->isRunningOnGae() && env('GAE_CACHE_ROUTES_FILE') === true)
        {
            return 'cachefs://bootstrap/cache/routes.php';
        }

        return parent::getCachedRoutesPath();
    }

    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        if ( ! $this->isRunningOnGae())
        {
           return parent::getCachedServicesPath();
        }

        if (env('GAE_CACHE_SERVICES_FILE') === true)
        {
            return 'cachefs://bootstrap/cache/services.json';
        }

        return $this->storagePath().'/framework/services.json';
    }


    /**
     * Detect if the application is running on GAE.
     */
    protected function detectGae()
    {
        if ( ! class_exists(self::GAE_ID_SERVICE)) {
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
            function($line, $depth, $indentPad)
            {
                if (-1 !== $depth)
                {
                    echo str_repeat($indentPad, $depth).$line."\n";
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
        if ($this->runningOnGae)
        {
            if ( ! is_null($this->gaeBucketPath))
            {
                return $this->gaeBucketPath;
            }

            $buckets = ini_get('google_app_engine.allow_include_gs_buckets');
            // Get the first bucket in the list.
            $bucket = current(explode(', ', $buckets));

            if ($bucket)
            {
                $this->gaeBucketPath = "gs://{$bucket}/storage";

                if (env('GAE_SKIP_GCS_INIT') === true)
                {
                    return $this->gaeBucketPath;
                }

                if ( ! file_exists($this->gaeBucketPath))
                {
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