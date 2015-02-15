<?php namespace Shpasser\GaeSupportL5\Foundation;

use Illuminate\Foundation\Application as IlluminateApplication;
use Illuminate\Http\Request;

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
        require_once(__DIR__ . '/gae_realpath.php');
        $this->gaeBucketPath = null;
        $this->detectGae();
        parent::__construct($basePath);
    }

    /**
     * Detect if the application is running on GAE.
     *
     * If we run on GAE then 'realpath()' function replacement
     * 'gae_realpath()' is declared, so it won't fail with GAE
     * bucket paths.
     *
     * In order for 'gae_realpath()' function to be called the code has
     * to be patched to use 'gae_realpath()' instead of 'realpath()'
     * using the command 'php artisan gae:deploy --config you@gmail.com'
     * from the terminal.
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
            if ( ! is_null($this->gaeBucketPath))
            {
                return $this->gaeBucketPath;
            }

            $buckets = ini_get('google_app_engine.allow_include_gs_buckets');
            // Get the first bucket in the list.
            $bucket = current(explode(', ', $buckets));

            if ($bucket) {
                $this->gaeBucketPath = "gs://{$bucket}/storage";

                if ( ! file_exists($this->gaeBucketPath)) {
                    mkdir($this->gaeBucketPath);
                    mkdir($this->gaeBucketPath.'/app');
                    mkdir($this->gaeBucketPath.'/framework');
                    mkdir($this->gaeBucketPath.'/framework/views');
                }

                return $this->gaeBucketPath;
            }
        }

        return $this->basePath.'/storage';
    }

}