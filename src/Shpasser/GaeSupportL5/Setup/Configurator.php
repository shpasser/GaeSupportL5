<?php namespace Shpasser\GaeSupportL5\Setup;

use Illuminate\Console\Command;

/**
 * Class Configurator
 *
 * @package Shpasser\GaeSupportL5\Setup
 */

class Configurator {

    protected $myCommand;

    /**
     * Constructs a new instance of Configurator class.
     *
     * @param Command $myCommand console
     * command to be used for console output.
     */
    public function __construct(Command $myCommand)
    {
        $this->myCommand = $myCommand;
    }

    /**
     * Configures a Laravel app to be deployed on GAE.
     *
     * @param string $appId the GAE application ID.
     * @param bool $generateConfig if 'true' => generate GAE config files(app.yaml and php.ini).
     * @param string $bucketId the custom GCS bucket ID, if 'null' the default bucket is used.
     * @param string $dbSocket Cloud SQL socket connection string.
     * @param string $dbName Cloud SQL database name.
     * @param string $dbHost Cloud SQL host IPv4 address.
     */
    public function configure($appId, $generateConfig, $bucketId,
                              $dbSocket, $dbName, $dbHost)
    {
        $env_file            = app_path().'/../.env';
        $env_production_file = app_path().'/../.env.production';
        $env_local_file      = app_path().'/../.env.local';
        $bootstrap_app_php   = app_path().'/../bootstrap/app.php';
        $config_app_php      = app_path().'/../config/app.php';
        $config_view_php     = app_path().'/../config/view.php';
        $config_mail_php     = app_path().'/../config/mail.php';
        $config_queue_php    = app_path().'/../config/queue.php';
        $config_databse_php  = app_path().'/../config/database.php';

        $this->createEnvProductionFile($env_file, $env_production_file, $dbSocket, $dbName);
        $this->createEnvLocalFile($env_file, $env_local_file, $dbHost, $dbName);
        $this->processFile($bootstrap_app_php, ['replaceAppClass']);
        $this->processFile($config_app_php, [
            'replaceLaravelServiceProviders',
            'setLogHandler'
        ]);
        $this->processFile($config_view_php, ['replaceRealpathFunction']);
        $this->processFile($config_mail_php, ['setMailDriver']);
        $this->processFile($config_queue_php, ['addQueueConfig']);
        $this->processFile($config_databse_php, ['addCloudSqlConfig']);

        if ($generateConfig)
        {
            $app_yaml   = app_path().'/../app.yaml';
            $publicPath = app_path().'/../public';
            $php_ini    = app_path().'/../php.ini';

            $this->generateAppYaml($appId, $app_yaml, $publicPath);
            $this->generatePhpIni($appId, $bucketId, $php_ini);
        }
    }

    /**
     * Creates a '.env.production' file based on the existing '.env' file.
     *
     * @param string $env_file The '.env' file path.
     * @param string $env_production_file The '.env.production' file path.
     * @param string $dbSocket Cloud SQL socket connection string.
     * @param string $dbName Cloud SQL database name.
     */
    protected function createEnvProductionFile($env_file, $env_production_file, $dbSocket, $dbName)
    {
        if (!file_exists($env_file))
        {
            $this->myCommand->error('Cannot find ".env" file to import the existing options.');
            return;
        }

        if (file_exists($env_production_file))
        {
            $overwrite = $this->myCommand->confirm(
                'Overwrite the existing ".env.production" file?', false
            );

            if ( ! $overwrite)
            {
                return;
            }
        }

        $env = new IniHelper;
        $env->read($env_file);

        $env['APP_ENV']   = 'production';
        $env['APP_DEBUG'] = 'false';

        $env['CACHE_DRIVER']   = 'memcached';
        $env['SESSION_DRIVER'] = 'memcached';

        $env['MAIL_DRIVER']  = 'gae';
        $env['QUEUE_DRIVER'] = 'gae';
        $env['LOG_HANDLER']  = 'syslog';

        if (( ! is_null($dbSocket)) && ( ! is_null($dbName)))
        {
            $env['CLOUD_SQL_SOCKET']    = $dbSocket;
            $env['CLOUD_SQL_HOST']      = '';
            $env['CLOUD_SQL_DATABASE']  = $dbName;
            $env['CLOUD_SQL_USERNAME']  = 'root';
            $env['CLOUD_SQL_PASSWORD']  = '';
        }

        $env->write($env_production_file);

        $this->myCommand->info('Created the ".env.production" file.');
    }


    /**
     * Creates a '.env.production' file based on the existing '.env' file.
     *
     * @param string $env_file The '.env' file path.
     * @param string $env_local_file The '.env.local' file path.
     * @param string $dbHost Cloud SQL host IPv4 address.
     * @param string $dbName Cloud SQL database name.
     */
    protected function createEnvLocalFile($env_file, $env_local_file, $dbHost, $dbName)
    {
        if (!file_exists($env_file))
        {
            $this->myCommand->error('Cannot find ".env" file to import the existing options.');
            return;
        }

        if (file_exists($env_local_file))
        {
            $overwrite = $this->myCommand->confirm(
                'Overwrite the existing ".env.local" file?', false
            );

            if ( ! $overwrite)
            {
                return;
            }
        }

        $env = new IniHelper;
        $env->read($env_file);

        $env['APP_ENV']   = 'local';
        $env['APP_DEBUG'] = 'true';

        $env['CACHE_DRIVER']   = 'file';
        $env['SESSION_DRIVER'] = 'file';

        if (( ! is_null($dbHost)) && ( ! is_null($dbName)))
        {
            $env['CLOUD_SQL_SOCKET']    = '';
            $env['CLOUD_SQL_HOST']      = $dbHost;
            $env['CLOUD_SQL_DATABASE']  = $dbName;
            $env['CLOUD_SQL_USERNAME']  = 'root';
            $env['CLOUD_SQL_PASSWORD']  = 'password';
        }

        $env->write($env_local_file);

        $this->myCommand->info('Created the ".env.local" file.');
    }

    /**
     * Processes a given file with given processors.
     *
     * @param  string $filePath   the path of the file to be processed.
     * @param  array  $processors array of processor function names to
     * be called during the file processing. Every such function shall
     * receive the file contents string as a parameter and return the
     * modified file contents.
     *
     * <code>
     * protected function processorFunc($contents)
     * {
     *     ...
     *     return $modified;
     * }
     * </code>
     */
    protected function processFile($filePath, $processors)
    {
        $contents = file_get_contents($filePath);

        $processed = $contents;

        foreach ($processors as $processor)
        {
            $processed = $this->$processor($processed);
        }

        if ($processed === $contents)
        {
            return;
        }

        $this->createBackupFile($filePath);

        file_put_contents($filePath, $processed);
    }

    /**
     * Processor function. Replaces the Laravel
     * application class with the one compatible with GAE.
     *
     * @param string $contents the 'bootstrap/app.php' file contents.
     *
     * @return string the modified file contents.
     */
    protected function replaceAppClass($contents)
    {
        $modified = str_replace(
            'Illuminate\Foundation\Application',
            'Shpasser\GaeSupportL5\Foundation\Application',
            $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Replaced the application class in "bootstrap/app.php".');
        }

        return $modified;
    }

    /**
     * Processor function. Replaces the Laravel
     * service providers with GAE compatible ones.
     *
     * @param string $contents the 'config/app.php' file contents.
     *
     * @return string the modified file contents.
     */
    protected function replaceLaravelServiceProviders($contents)
    {
        $strings = [
            'Illuminate\Mail\MailServiceProvider',
            'Illuminate\Queue\QueueServiceProvider'
        ];

		// Replacement to:
		//  - additionally support Google App Engine Queues,
        //  - additionally support Google App Engine Mail.
        $replacements = [
            'Shpasser\GaeSupportL5\Mail\MailServiceProvider',
            'Shpasser\GaeSupportL5\Queue\QueueServiceProvider'
        ];

        $modified = str_replace($strings, $replacements, $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Replaced the service providers in "config/app.php".');
        }

        return $modified;
    }


    /**
     * Processor function. Sets the syslog log handler
     * for a Laravel GAE app.
     *
     * @param string $contents the 'config/app.php' file contents.
     *
     * @return string the modified file contents.
     */
    protected function setLogHandler($contents)
    {
        $expression = "/'log'.*=>[^env\(]*'\b.+\b'/";
        $replacement = "'log' => env('LOG_HANDLER', 'daily')";

        $modified = preg_replace($expression, $replacement, $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Set the log handler in "config/app.php".');
        }

        return $modified;
    }

    /**
     * Processor function. Replaces a realpath() function call
     * with call to a GAE storage bucket compatible gae_realpath() call.
     *
     * @param string $contents the 'config/view.php' file contents.
     * @return string the modified file contents.
     */
    protected function replaceRealpathFunction($contents)
    {
        $modified = preg_replace(
            '/\brealpath\s*\(\s*storage_path/',
            'gae_realpath(storage_path',
            $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Replaced the realpath() function call in "config/view.php".');
        }

        return $modified;
    }

    /**
     * Processor function. Sets the mail driver
     * for a Laravel GAE app.
     *
     * @param string $contents the 'config/mail.php' file contents.
     *
     * @return string the modified file contents.
     */
    protected function setMailDriver($contents)
    {
        $expression = "/'driver'.*=>[^env\(]*'\b.+\b'/";
        $replacement = "'driver' => env('MAIL_DRIVER', 'smtp')";

        $modified = preg_replace($expression, $replacement, $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Set the mail driver in "config/mail.php".');
        }

        return $modified;
    }

    /**
     * Adds the GAE queue configuration to the 'config/queue.php'
     * if it does not already exist.
     *
     * @param string $contents the 'config/queue.php' file contents.
     * @return string the modified file contents.
     */
    protected function addQueueConfig($contents)
    {
        if (str_contains($contents, "'gae'"))
        {
            return $contents;
        }

        $expression = "/'connections'\s*=>\s*\[/";

        $replacement =
<<<EOT
'connections' => [

        'gae' => [
            'driver'	=> 'gae',
            'queue'		=> 'default',
            'url'		=> '/tasks',
            'encrypt'	=> true,
        ],
EOT;
        $modified = preg_replace($expression, $replacement, $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Added queue driver configuration in "config/queue.php".');
        }

        return $modified;
    }


    protected function addCloudSqlConfig($contents)
    {
        if (str_contains($contents, "'cloudsql'"))
        {
            return $contents;
        }

        $expression = "/'connections'\s*=>\s*\[/";

        $replacement =
<<<EOT
'connections' => [

    'cloudsql' => [
        'driver'      => 'mysql',
        'unix_socket' => env('CLOUD_SQL_SOCKET')
        'host'        => env('CLOUD_SQL_HOST'),
        'database'    => env('CLOUD_SQL_DATABASE'),
        'username'    => env('CLOUD_SQL_USERNAME'),
        'password'    => env('CLOUD_SQL_PASSWORD'),
        'charset'     => 'utf8',
        'collation'   => 'utf8_unicode_ci',
        'prefix'      => '',
        'strict'      => false,
    ],
EOT;

        $modified = preg_replace($expression, $replacement, $contents);

        if ($contents !== $modified)
        {
            $this->myCommand->info('Added Cloud SQL driver configuration in "config/database.php".');
        }

        return $modified;
    }

    /**
     * Generates a "app.yaml" file for a GAE app.
     *
     * @param string $appId the GAE app id.
     * @param string $filePath the 'app.yaml' file path.
     * @param string $publicPath the application public dir path.
     */
    protected function generateAppYaml($appId, $filePath, $publicPath)
    {
        if (file_exists($filePath))
        {
            $overwrite = $this->myCommand->confirm(
                'Overwrite the existing "app.yaml" file?', false
            );

            if ( ! $overwrite)
            {
                return;
            }
        }

        $pathMappings = '';
        foreach (new \DirectoryIterator($publicPath) as $fileInfo)
        {
            if($fileInfo->isDot() || ! $fileInfo->isDir())
            {
                continue;
            }

            $dirName = $fileInfo->getFilename();
            $pathMappings .= PHP_EOL.
<<<EOT
        - url: /{$dirName}
          static_dir: public/{$dirName}

EOT;
        }

        $contents =
<<<EOT
application:    {$appId}
version:        1
runtime:        php
api_version:    1

handlers:
        - url: /favicon\.ico
          static_files: public/favicon.ico
          upload: public/favicon\.ico
{$pathMappings}
        - url: /.*
          script: public/index.php

skip_files:
        - ^(.*/)?#.*#$
        - ^(.*/)?.*~$
        - ^(.*/)?.*\.py[co]$
        - ^(.*/)?.*/RCS/.*$
        - ^(.*/)?\.(?!env).*$
        - ^(.*/)?node_modules.*$
        - ^(.*/)?_ide_helper\.php$
EOT;
        file_put_contents($filePath, $contents);

        $this->myCommand->info('Generated the "app.yaml" file.');
    }

    /**
     * Generates a "php.ini" file for a GAE app.
     *
     * @param string $appId the GAE app id.
     * @param string $bucketId the GAE gs-bucket id.
     * @param string $filePath the 'php.ini' file path.
     */
    protected function generatePhpIni($appId, $bucketId, $filePath)
    {
        if (file_exists($filePath))
        {
            $overwrite = $this->myCommand->confirm(
                'Overwrite the existing "php.ini" file?', false
            );

            if ( ! $overwrite)
            {
                return;
            }
        }

        $storageBucket = "{$appId}.appspot.com";
        if ($bucketId !== null)
        {
            $storageBucket = $bucketId;
        }

        $contents =
<<<EOT
; enable function that are disabled by default in the App Engine PHP runtime
google_app_engine.enable_functions = "php_sapi_name, php_uname, getmypid"
google_app_engine.allow_include_gs_buckets = "{$storageBucket}"
allow_url_include = 1
EOT;
        file_put_contents($filePath, $contents);

        $this->myCommand->info('Generated the "php.ini" file.');
    }

    /**
     * Creates a backup copy of a desired file.
     *
     * @param string $filePath the file path.
     */
    protected function createBackupFile($filePath)
    {
        $sourcePath = $filePath;
        $backupPath = $filePath.'.bak';

        if (file_exists($backupPath))
        {
            $date = new \DateTime();
            $backupPath = "{$filePath}{$date->getTimestamp()}.bak";
        }

        copy($sourcePath, $backupPath);
    }

}
