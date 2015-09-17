<?php

namespace Shpasser\GaeSupportL5\Storage;

use Dotenv;
use InvalidArgumentException;

/**
 * Initializes caching of Laravel 5.1 configuration files on GAE.
 */
class Optimizer
{
    const CONFIG_PATH = 'cachefs://bootstrap/cache';
    const COMPILED_VIEWS_PATH = 'cachefs://framework/views';

    /**
     * @var boolean
     */
    protected $runningInConsole;

    /**
     * Configuration file paths.
     * @var string
     */
    protected $configPath;
    protected $routesPath;
    protected $servicesPath;

    /**
     * Application base path.
     * @var string
     */
    protected $basePath;

    /**
     * Keep track of cached files, cache only once.
     * @var array
     */
    protected $cachedFiles;

    /**
     * @var boolean
     */
    protected $initialized;


    /**
     * Constructs an instance of GaeCacheManager.
     *
     * @param string $basePath Laravel base path.
     * @param boolean $runningInConsole 'true' if running in console.
     */
    public function __construct($basePath, $runningInConsole)
    {
        $this->basePath = $basePath;
        $this->runningInConsole = $runningInConsole;
        $this->initialized = false;
        $this->cachedFiles = array();

        $this->configPath   = self::CONFIG_PATH.'/config.php';
        $this->routesPath   = self::CONFIG_PATH.'/routes.php';
        $this->servicesPath = self::CONFIG_PATH.'/services.json';
    }


    /**
     * Bootstraps the Optimizer.
     *
     * @return boolean 'true' if successful, otherwise 'false'.
     */
    public function bootstrap()
    {
        if (! $this->runningInConsole && $this->initializeFs()) {
            $this->buildFsTree();
            $this->initialized = true;
        }

        return $this->initialized;
    }


    /**
     * Get the path to the configuration cache file.
     *
     * @return string
     */
    public function getCachedConfigPath()
    {
        if ($this->initialized && env('CACHE_CONFIG_FILE')) {
            $this->cacheFile($this->basePath.'/bootstrap/cache/config.php', $this->configPath);
            return $this->configPath;
        }

        return false;
    }


    /**
     * Get the path to the routes cache file.
     *
     * @return string
     */
    public function getCachedRoutesPath()
    {
        if ($this->initialized && env('CACHE_ROUTES_FILE')) {
            $this->cacheFile($this->basePath.'/bootstrap/cache/routes.php', $this->routesPath);
            return $this->routesPath;
        }

        return false;
    }


    /**
     * Get the path to the cached services.json file.
     *
     * @return string
     */
    public function getCachedServicesPath()
    {
        return  ($this->initialized && env('CACHE_SERVICES_FILE')) ? $this->servicesPath : false;
    }

    /**
     * Initializes the Cache Filesystem.
     */
    protected function initializeFs()
    {
        return CacheFs::initialize();
    }

    /**
     * Builds a filesystem tree in 'cachefs'.
     */
    protected function buildFsTree()
    {
        mkdir(self::CONFIG_PATH, 0777, true);
        mkdir(self::COMPILED_VIEWS_PATH, 0777, true);
    }

    /**
     * Adds the requested file to cache.
     *
     * @param string $path path to the file to be cached.
     * @param string $cachefsPath path for the cached file(under 'cachefs://').
     */
    protected function cacheFile($path, $cachefsPath)
    {
        if (array_key_exists($path, $this->cachedFiles)) {
            return;
        }

        if (file_exists($path)) {
            $contents = file_get_contents($path);
            file_put_contents($cachefsPath, $contents);

            $this->cachedFiles[$path] = $cachefsPath;
        }
    }
}
