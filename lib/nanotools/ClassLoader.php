<?php

namespace nanotools;
use Exception;

/**
 * A PSR4 compliant ClassLoader.
 */
class ClassLoader {

    const LOAD_METHOD = 'load';

    private $namespacePrefix;
    private $directories = [];

    /**
     * Create a ClassLoader instance.
     * @param string $namespacePrefix The prefix stripped from the class name when loading.
     */
    public function __construct($namespacePrefix = "") {
        $this->namespacePrefix = $namespacePrefix;
    }

    /**
     * Create and register a ClassLoader for a single directory and namespace prefix.
     * @param string $directory The source root directory.
     * @param string $namespacePrefix The prefix stripped from the class name when loading.
     */
    public static function mount($directory, $namespacePrefix = "") {
        $classLoader = new ClassLoader($namespacePrefix);
        $classLoader->addDirectory($directory);
        spl_autoload_register([$classLoader, self::LOAD_METHOD]);
    }

    /**
     * Add a source root directory to this ClassLoader.
     * @param string $directory The source root directory.
     */
    public function addDirectory($directory) {
        // Normalize. Just in case.
        str_replace('\\', '/', $directory);
        $directory = rtrim($directory, '/') . '/';
        $this->checkDirectory($directory);
        array_push($this->directories, $directory);
    }

    /**
     * Register this ClassLoader.
     * @param bool $prepend Whether to put prepend it before existing ClassLoaders.
     */
    public function register($prepend = false) {
        spl_autoload_register([$this, self::LOAD_METHOD], true, $prepend);
    }

    /**
     * Unregister this ClassLoader.
     */
    public function unregister() {
        spl_autoload_unregister([$this, self::LOAD_METHOD]);
    }

    /**
     * Load the class.
     * @param string $class The fully qualified class name.
     */
    public function load($class) {
        // Does the class have our namespace prefix?
        $prefixLength = strlen($this->namespacePrefix);
        if (strncmp($this->namespacePrefix, $class, $prefixLength) !== 0) {
            return; // Allow other loaders to require the file.
        }

        // Get the relative path in source directory.
        $classAfterPrefix = substr($class, $prefixLength);
        $pathInSourceDirectory = str_replace('\\', '/', $classAfterPrefix) . '.php';

        foreach ($this->directories as $sourceDirectory) {
            $file = $sourceDirectory . $pathInSourceDirectory;
            if (is_readable($file)) { // Allow other loaders to require the file.
                require $file;
                return; // Found the file, stop now.
            }
        }
    }

    private function checkDirectory($directory) {
        if (!file_exists($directory) || !is_dir($directory)) {
            throw new Exception("Directory does not exist: $directory.");
        }
    }

}
