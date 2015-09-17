<?php

namespace Shpasser\GaeSupportL5\Storage;

use Memcached;

/**
 * A Stream Wrapper for Cache File System.
 *
 * See: http://www.php.net/manual/en/class.streamwrapper.php
 */
final class CacheFs
{
    /**
     * This property must be public so PHP can populate
     * it with the actual context resource.
     * @var resource
     */
    public $context;

    private $fd;
    private $mode;
    private $path;

    private static $folders = [];

    private $dir;

    const DIR_MODE  = 040777;
    const FILE_MODE = 0100777;
    const PROTOCOL  = 'cachefs';


    /**
     * @var Memcached
     */
    private static $memcached = null;

    /**
     * Register the stream wrapper only once
     * @var boolean
     */
    private static $registered = false;


    /**
     * Establishes a connection to memcached server and
     * registers the Stream Wrapper.
     *
     * @return boolean 'true' if connection was successful, 'false' otherwise.
     */
    public static function initialize()
    {
        if (self::$registered) {
            return true;
        }

        try {
            // initialize the connection to memcached
            if (is_null(self::cache())) {
                return false;
            }
            // register the wrapper
            stream_wrapper_register(self::PROTOCOL, 'Shpasser\GaeSupportL5\Storage\CacheFs');
            self::$registered = true;
        } catch (\RuntimeException $ex) {
            return false;
        }

        return true;
    }

    /**
     * Returns the memcached instance.
     *
     * @return Memcached
     * @throws RuntimeException
     */
    private static function cache()
    {
        if (is_null(self::$memcached) && class_exists('Memcached')) {
            $servers = [['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100]];

            self::$memcached = new Memcached();

            foreach ($servers as $server) {
                self::$memcached->addServer(
                    $server['host'], $server['port'], $server['weight']
                );
            }

            if (self::$memcached->getVersion() === false) {
                throw new \RuntimeException("Could not establish Memcached connection.");
            }
        }

        return self::$memcached;
    }

    private function dir_list($path)
    {
        $dirPath = $path.'/';
        $length = strlen($dirPath);
        $paths = array_merge(self::cache()->getAllKeys(), self::$folders);

        $dir = array_filter($paths, function ($file) use ($dirPath,$length) {
            if (substr($file, 0, $length) === $dirPath
                && strrpos($file, '/') == $length) {
                return true;
            }
            return false;
        });

        return $dir;
    }

    /**
     * Constructs a new stream wrapper.
     */
    public function __construct()
    {
        $this->fd = null;
        $this->mode = null;
        $this->path = null;
    }

    /**
     * Destructs an existing stream wrapper.
     */
    public function __destruct()
    {
    }

    /**
     * Renames a storage object.
     *
     * @return true if the object was renamed, false otherwise
     */
    public function rename($from, $to)
    {
        // TODO: add directories support

        $contents = self::cache()->get($from);
        if (false === $contents) {
            return false;
        }

        self::cache()->delete($from);
        self::cache()->set($to, $contents);

        return true;
    }

    /**
     * Closes the stream
     */
    public function stream_close()
    {
        $this->stream_flush();
        fclose($this->fd);
        $this->path = null;
        $this->fd = null;
    }

    /**
    * Tests for end-of-file on a file pointer.
    *
    * @return true if the read/write position is at the end of the stream and if
    * no more data is available to be read, otherwise false
    */
    public function stream_eof()
    {
        return feof($this->fd);
    }

    /**
     * Flushes the output.
     *
     * @return true if the cached data was successfully stored (or if there was
     * no data to store), false if the data could not be stored.
     */
    public function stream_flush()
    {
        switch ($this->mode) {
            case 'r+':
            case 'rb+':
            case 'w':
            case 'wb':
            case 'w+':
            case 'wb+':
            case 'a':
            case 'ab':
            case 'a+':
            case 'ab+':
                $origPos = ftell($this->fd);
                fseek($this->fd, 0, SEEK_END);
                $size = ftell($this->fd);
                fseek($this->fd, 0, SEEK_SET);
                $contents = fread($this->fd, $size);
                fseek($this->fd, $origPos, SEEK_SET);
                self::cache()->set($this->path, $contents);
                break;
        }

        return true;
    }

    /**
     * Stream metadata is not supported.
     *
     * @param $path
     * @param $option
     * @param $value
     * @return bool always false.
     */
    public function stream_metadata($path, $option, $value)
    {
        return false;
    }

    /**
     * Opens a stream.
     *
     * @param $path
     * @param $mode
     * @param $options
     * @param $opened_path
     * @return bool true on success, false otherwise.
     * @throws RuntimeException
     */
    public function stream_open($path, $mode, $options, &$opened_path)
    {
        $contents = self::cache()->get($path);
        $fileExists = (false !== $contents);

        switch ($mode) {
            case 'r':
            case 'rb':
            case 'r+':
            case 'rb+':
                if (! $fileExists) {
                    return false;
                }

                $this->fd = fopen('php://memory', "wb+");
                fwrite($this->fd, $contents);
                fseek($this->fd, 0, SEEK_SET);
                break;

            case 'w':
            case 'wb':
            case 'w+':
            case 'wb+':
                $this->fd = fopen('php://memory', "wb+");
                break;

            case 'a':
            case 'ab':
            case 'a+':
            case 'ab+':
                $this->fd = fopen('php://memory', "wb+");

                if ($fileExists) {
                    fwrite($this->fd, $contents);
                    fseek($this->fd, 0, SEEK_END);
                }
                break;

            default:
                return false;
        }

        $this->path = $path;
        $this->mode = $mode;

        return true;
    }


    /**
     * Reads from a stream.
     *
     * @return string string of bytes.
     */
    public function stream_read($count)
    {
        return fread($this->fd, $count);
    }

    /**
     * Performs a seek operation on a stream.
     *
     * @param $offset
     * @param $whence
     * @return int
     */
    public function stream_seek($offset, $whence)
    {
        return fseek($this->fd, $offset, $whence);
    }

    /**
     * Stream option setting is not supported.
     *
     * @return bool always false.
     */
    public function stream_set_option($option, $arg1, $arg2)
    {
        return false;
    }

    /**
     * Returns a stream stat information.
     *
     * @return array stat information.
     */
    public function stream_stat()
    {
        return $this->url_stat($this->path, 0);
    }

    /**
     * Returns the current position in a stream.
     *
     * @return int the position.
     */
    public function stream_tell()
    {
        return ftell($this->fd);
    }

    /**
     * Returns the number of bytes written.
     */
    public function stream_write($data)
    {
        return fwrite($this->fd, $data);
    }

    /**
     * Deletes a file. Called in response to unlink($filename).
     */
    public function unlink($path)
    {
        if (false === self::cache()->get($path)) {
            return false;
        }

        self::cache()->delete($path);

        return true;
    }

    /**
     * Returns stat information for a given path.
     *
     * @param  string $path
     * @param  int $flags
     * @return array stat information.
     */
    public function url_stat($path, $flags)
    {
        $now  = time();
        $stat = [
            'dev'    =>    0, // no specific device number
            'ino'    =>    0, // no inode number
            'mode'    =>    0, // inode protection mode
            'nlink'    =>    1, // number of links
            'uid'    =>    0, // no userid of owner
            'gid'    =>    0, // no groupid of owner
            'rdev'    =>    0, // no device type, not inode device
            'size'    =>    0, // size in bytes
            'atime'    =>    $now, // time of last access (Unix timestamp)
            'mtime'    =>    $now, // time of last modification (Unix timestamp)
            'ctime' =>    $now, // time of last inode change (Unix timestamp)
            'blksize' => 512, // blocksize of filesystem IO
            'blocks'  => 0, // number of 512-byte blocks allocated **
        ];

        if (array_has(self::$folders, $path)) {
            $stat['mode'] = self::DIR_MODE;

            return $stat;
        }

        $contents = self::cache()->get($path);

        if (false === $contents) {
            return false;
        }

        $size = strlen($contents);
        $stat['mode']   = self::FILE_MODE;
        $stat['size']   = $size;
        $stat['blocks'] = (int)(($size + 512) / 512);

        return $stat;
    }

    /**
     * Closes directory listing operation.
     *
     * @return bool always true.
     */
    public function dir_closedir()
    {
        $this->dir = null;
        return true;
    }

    /**
     * Opens directory listing operation.
     *
     * @return bool always true.
     */
    public function dir_opendir($path, $options)
    {
        $this->dir = $this->dir_list($path);
        return true;
    }

    /**
     * Returns the nex element in directory listing.
     *
     * @return string the next directory element name.
     */
    public function dir_readdir()
    {
        return next($this->dir);
    }

    /**
     * Restarts directory listing operation.
     *
     * @return bool always true.
     */
    public function dir_rewinddir()
    {
        reset($this->dir);
        return true;
    }

    /**
     * Creates a directory.
     *
     * @param string $path
     * @param int $mode
     * @param int $options
     *
     * @return boot true on success, false otherwise.
     */
    public function mkdir($path, $mode, $options)
    {
        if (array_has(self::$folders, $path)) {
            return true;
        }

        $parentEnd = strrpos($path, '/');
        $parent = substr($path, 0, $parentEnd);

        if (array_has(self::$folders, $parent)) {
            self::$folders[] = $path;
            return true;
        }

        if (! ($options & STREAM_MKDIR_RECURSIVE)) {
            return false;
        }

        while ($path !== self::PROTOCOL.':/') {
            if (array_has(self::$folders, $path)) {
                break;
            } else {
                self::$folders[] = $path;
            }

            $path = substr($path, 0, $parentEnd);
            $parentEnd = strrpos($path, '/');
        }

        return true;
    }

    /**
     * Removes a directory.
     *
     * @param string $path
     * @param int $options
     *
     * @return boot true on success, false otherwise.
     */
    public function rmdir($path, $options)
    {
        $dir = $this->dir_list($path);

        if (empty($dir)) {
            $count = count(self::$folders);
            for ($index = 0; index < $count; $index++) {
                if (self::$folders[$index] === $path) {
                    unset(self::$folders[$index]);
                    return true;
                }
            }
        }

        return false;
    }
}
