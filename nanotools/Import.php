<?php

class Import {

    const DIRECTORY_SEPARATOR = '/';

    protected $directory;

    public static final function directory($directory_name) {
        if (empty($directory_name)) {
            throw new Exception("Undefined directory name");
        }
        spl_autoload_register([new Import($directory_name), 'load']);
    }

    private final function __construct($directory_name) {
        $this->directory = $directory_name;
    }

    public function load($classname) {
        $filename = $classname . '.php';
        $path = $this->directory . self::DIRECTORY_SEPARATOR . $filename;
        if (file_exists($path)) { // Allow other loaders to include the file.
            require($path);
        }
    }
}
