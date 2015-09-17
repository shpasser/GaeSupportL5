<?php

namespace Shpasser\GaeSupportL5\Setup;

class IniHelper implements \ArrayAccess
{
    /**
     * INI configuration array
     * @var array
     */
    protected $config;

    /**
     * Last loaded INI file path.
     *
     * @var string
     */
    protected $filePath;

    /**
     * Reads a INI file and parses its values into an array.
     *
     * @param string $file The file path to parse.
     */
    public function read($file)
    {
        $this->filePath = $file;
        $this->config = parse_ini_file($this->filePath);
    }

    /**
     * Writes the configuration data back to the INI file.
     *
     * @param string $file If not empty will be used as an
     * INI file path to be written.
     */
    public function write($file = "")
    {
        $iniString = $this->generateIniString($this->config);
        file_put_contents($file ? $file : $this->filePath, $iniString);
    }

    /**
     * Generates an INI string from a given associative array.
     *
     * @param array $array the array containing the INI data.
     * @return string
     */
    protected function generateIniString($array)
    {
        $iniString = "";

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $iniString .= "[{$key}]".PHP_EOL;
                $iniString .= $this->generateIniString($value);
            } else {
                $iniString .= "{$key}={$value}".PHP_EOL;
            }
        }

        return $iniString;
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
            $this->config[] = $value;
        } else {
            $this->config[$offset] = $value;
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
        return isset($this->config[$offset]);
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
        unset($this->config[$offset]);
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
        return isset($this->config[$offset]) ? $this->config[$offset] : null;
    }
}
