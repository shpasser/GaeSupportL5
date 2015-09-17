<?php

namespace Shpasser\GaeSupportL5\Setup;

use ZipArchive;

/**
 * Class ConfiguratorTest
 *
 * @package Shpasser\GaeSupport\Setup
 */
class ConfiguratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Helper function.
     *
     * Deletes a directory tree.
     *
     * @param string $dir the root of the directory tree to delete.
     * @return bool 'true' if successful, 'false' otherwise.
     */
    protected static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.', '..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    /**
     * Initializes the 'testee' object and prepares,
     * the 'playground', fake configuration files.
     */
    protected function setUp()
    {
        require_once __DIR__.'/FakeHelpers.php';

        // Prepare the playground.
        $zip = new ZipArchive();
        $zip->open(__DIR__.'/resources.zip');
        $zip->extractTo(__DIR__.'/playground');

        // Call the configure() function on the 'testee'
        // to generate/modify the files.
        $configurator = new Configurator(new FakeCommand);

        $appId = 'laravel-app-gae-id';
        $generateConfig = true;
        $cacheConfig = false;
        $bucketId = null;
        $dbSocket = '/cloudsql/test-app:test-cloud-sql';
        $dbName   = 'gae_test_db';
        $dbHost   = 'XXX.XXX.XXX.XXX';

        $configurator->configure($appId, $generateConfig, $cacheConfig, $bucketId, $dbSocket, $dbName, $dbHost);
    }

    /**
     * Cleans up the 'playground' with all of its contents.
     */
    protected function tearDown()
    {
        self::delTree(__DIR__.'/playground');
    }

    public function testEnvProductionGeneration()
    {
        $env_production = __DIR__.'/playground/.env.production';
        $expected       = __DIR__.'/playground/.env.production_expected_result';
        $this->assertFileEquals($env_production, $expected);
    }

    public function testEnvLocalGeneration()
    {
        $env_local = __DIR__.'/playground/.env.local';
        $expected  = __DIR__.'/playground/.env.local_expected_result';
        $this->assertFileEquals($env_local, $expected);
    }

    public function testBootstrapAppModification()
    {
        $bootstrap_app_php = __DIR__.'/playground/bootstrap/app.php';
        $expected          = __DIR__.'/playground/bootstrap/app.php_expected_result';
        $this->assertFileEquals($bootstrap_app_php, $expected);
    }

    public function testConfigAppModification()
    {
        $config_app_php = __DIR__.'/playground/config/app.php';
        $expected       = __DIR__.'/playground/config/app.php_expected_result';
        $this->assertFileEquals($config_app_php, $expected);
    }

    public function testConfigViewModification()
    {
        $config_view_php = __DIR__.'/playground/config/view.php';
        $expected        = __DIR__.'/playground/config/view.php_expected_result';
        $this->assertFileEquals($config_view_php, $expected);
    }

    public function testConfigQueueModification()
    {
        $config_queue_php = __DIR__.'/playground/config/queue.php';
        $expected         = __DIR__.'/playground/config/queue.php_expected_result';
        $this->assertFileEquals($config_queue_php, $expected);
    }

    public function testConfigDatabaseModification()
    {
        $config_queue_php = __DIR__.'/playground/config/database.php';
        $expected         = __DIR__.'/playground/config/database.php_expected_result';
        $this->assertFileEquals($config_queue_php, $expected);
    }

    public function testGenerateAppYaml()
    {
        $app_yaml   = __DIR__.'/playground/app.yaml';
        $expected   = __DIR__.'/playground/app.yaml_expected_result';
        $this->assertFileEquals($app_yaml, $expected);
    }

    public function testGeneratePhpIni()
    {
        $php_ini  = __DIR__.'/playground/php.ini';
        $expected = __DIR__.'/playground/php.ini_expected_result';
        $this->assertFileEquals($php_ini, $expected);
    }
}
