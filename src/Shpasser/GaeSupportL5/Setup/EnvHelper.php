<?php

namespace Shpasser\GaeSupportL5\Setup;

class EnvHelper implements \ArrayAccess
{
    /**
     * ENV configuration array
     * @var array
     */
    protected $lines;

    /**
     * Last loaded ENV file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Reads a ENV file into an array.
     *
     * @param string $file The file path to parse.
     */
    public function read($file)
    {
        $this->filePath = $file;
        $this->lines = file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    }

    /**
     * Writes the configuration data back to the ENV file.
     *
     * @param string $file If not empty will be used as an
     * ENV file path to be written.
     */
    public function write($file = "")
    {
        $envString = $this->generateEnvString($this->lines);
        file_put_contents($file ? $file : $this->filePath, $envString);
    }

    /**
     * Generates ENV string from a given associative array.
     *
     * @param array $array the array containing ENV data.
     * @return string
     */
    protected function generateEnvString($array)
    {
        $envString = "";

        foreach ($array as $value) {
            $envString .= "{$value}".PHP_EOL;
        }

        return $envString;
    }

    /**
     * Parses key and value for a given line.
     *
     * @param  string $line   the line to be parsed.
     * @param  string &$key   the parsed key.
     * @param  string &$value the parsed value.
     * @return boolean true if parsing was successful, false otherwise.
     */
    protected function parseEnvLine($line, &$key, &$value)
    {
        $parseOk = false;

        $keyExpr = '/(^\S+)\s*=\s*/';
        if (preg_match($keyExpr, $line, $matched) === 1) {
            $key = $matched[1];
            $value = substr($line, strlen($matched[0]));
            $parseOk = true;
        }

        return $parseOk;
    }

    /**
     * Finds the line containing the given key.
     *
     * @param  string $key the key.
     * @return integer the line index > 0, otherwise -1.
     */
    protected function findLine($key)
    {
        $parsedKey   = null;
        $parsedValue = null;

        foreach ($this->lines as $index => $line) {
            $parseOk = $this->parseEnvLine($line, $parsedKey, $parsedValue);
            if ($parseOk && $key === $parsedKey) {
                return $index;
            }
        }

        return -1;
    }

    /**
     * Assigns a value to the specified offset
     *
     * @param string $offset The offset to assign the value to
     * @param mixed $value The value to set
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->lines[] = $value;
        } else {
            $index = $this->findLine($offset);

            if ($index >= 0) {
                $this->lines[$index] = "{$offset}={$value}";
            } else {
                $this->lines[] = "{$offset}={$value}";
            }
        }
    }

    /**
     * Whether or not an offset exists
     *
     * @param string $offset An offset to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        $lineFound = false;

        $index = $this->findLine($offset);

        if ($index >= 0) {
            $lineFound = true;
        }

        return $lineFound;
    }

    /**
     * Un-sets an offset
     *
     * @param string $offset The offset to unset
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset)
    {
        $index = $this->findLine($offset);

        if ($index >= 0) {
            unset($this->lines[$index]);
        }
    }

    /**
     * Returns the value at specified offset
     *
     * @param string $offset The offset to retrieve
     * @access public
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset)
    {
        $index = $this->findLine($offset);

        if ($index > 0) {
            $parsedKey   = null;
            $parsedValue = null;

            $parseOk = $this->parseEnvLine($this->lines[$index], $parsedKey, $parsedValue);

            return $parseOk ? $parsedValue : null;
        }

        return null;
    }
}
