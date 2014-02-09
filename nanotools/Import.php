<?php

class Import {

    const DIRECTORY_SEPARATOR = '/';

    protected $directory;

    /**
     * Registers an import for the provided directory. Does not explore recursively.
     * @param string $directory The directory to search.
     * @throws Exception
     */
    public static final function directory($directory) {
        if (empty($directory) || !file_exists($directory) || !is_dir($directory)) {
            throw new Exception("Directory does not exist: $directory.");
        }
        spl_autoload_register([new Import($directory), 'load']);
    }

    private final function __construct($directory) {
        $this->directory = $directory;
    }

    public function load($class) {
        $filename = $class . '.php';
        $path = $this->directory . self::DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) { // Allow other loaders to include the file.
            require($path);
        }
    }
}
