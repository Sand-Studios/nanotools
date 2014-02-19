<?php

namespace nanotools;

use Exception;

class Import {

    const NAMESPACE_SEPARATOR = '\\';

    private $directory;

    private final function __construct($directory) {
        $this->directory = $directory;
    }

    /**
     * Registers a custom autoload handler.
     * @param callable $handler The custom handler.
     */
    public static final function handler(callable $handler) {
        spl_autoload_register($handler);
    }

    /**
     * Registers an autoloader for the provided directory. Does not explore recursively.
     * @param string $directory The directory to search.
     * @throws Exception When directory does not exist.
     */
    public static final function directory($directory) {
        self::checkDirectory($directory);
        spl_autoload_register([new Import($directory), 'loadClassFromDirectory']);
    }

    /**
     * Registers an autoloader for the provided directory. Loads based on namespaces and PSR0.
     * @param string $directory The directory to search.
     * @throws Exception When directory does not exist.
     */
    public static final function namespaced($directory) {
        self::checkDirectory($directory);
        spl_autoload_register([new Import($directory), 'loadClassFromDirectoryNamespaced']);
    }

    private static function checkDirectory($directory) {
        if (empty($directory) || !file_exists($directory) || !is_dir($directory)) {
            throw new Exception("Directory does not exist: $directory.");
        }
    }

    public function loadClassFromDirectory($className) {
        $filename = $className . '.php';
        $path = $this->directory . DIRECTORY_SEPARATOR . $filename;
        $this->load($path);
    }

    public function loadClassFromDirectoryNamespaced($className) {
        $namespacePath = '';
        $lastNamespacePosition = strripos($className, self::NAMESPACE_SEPARATOR);
        if (false !== $lastNamespacePosition) {
            $namespace = substr($className, 0, $lastNamespacePosition);
            $className = substr($className, $lastNamespacePosition + 1);
            $namespacePath = str_replace(self::NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
        }
        $namespacePath .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';

        $path = (is_null($this->directory) ? '' : $this->directory . DIRECTORY_SEPARATOR) . $namespacePath;
        $this->load($path);
    }

    private function load($path) {
        if (is_readable($path)) { // Allow other loaders to include the file.
            require $path;
        }
    }

}
