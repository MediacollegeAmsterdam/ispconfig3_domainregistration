<?php

namespace Domainregistration\Util;

final class Autoloader
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var int
     */
    private $prefixLength;

    /**
     * @param string $prefix
     */
    public function __construct($prefix)
    {
        $this->prefix = $prefix;
        $this->prefixLength = mb_strlen($this->prefix);
    }

    /**
     * @param string $class
     * @return void
     */
    public function autoload($class)
    {
        if (!$this->classExistsWithinPrefix($class)) {
            return;
        }

        $filename = $this->determineFilename($class);

        if (file_exists($filename)) {
            require_once $filename;
        }
    }

    /**
     * @param string $class
     * @return bool
     */
    private function classExistsWithinPrefix($class)
    {
        return strncmp($this->prefix, $class, $this->prefixLength) === 0;
    }

    /**
     * @param string $class
     * @return string
     */
    private function determineFilename($class)
    {
        $baseDir = sprintf('%s/../', __DIR__);
        $relativeClass = substr($class, $this->prefixLength);

        return sprintf('%s%s', $baseDir, str_replace('\\', '/', $relativeClass) . '.php');
    }
}
