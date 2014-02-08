<?php

class Template {

    const DIRECTORY_SEPARATOR = '/';

    private $viewDirectory;
    private $layoutFile;

    private $vars = [];

    public final function __construct($viewDirectory, $layoutFileName) {
        if (empty($viewDirectory) || !file_exists($viewDirectory) || !is_dir($viewDirectory)) {
            throw new Exception("Directory does not exist: $viewDirectory.");
        }

        if (empty($layoutFileName)) {
            throw new Exception("No layout file provided.");
        }

        $this->viewDirectory = $viewDirectory;
        $this->layoutFile = $this->viewDirectory . self::DIRECTORY_SEPARATOR . $layoutFileName . '.php';

        $this->checkFileExists($this->layoutFile);
    }

    public function assign($key, $value) {
        $this->vars[$key] = $value;
    }

    public function renderPartial($script) {
        $scriptFile = $this->viewDirectory . self::DIRECTORY_SEPARATOR . $script . '.php';
        $this->checkFileExists($scriptFile);

        // Set assigned vars for local script scope.
        foreach ($this->vars as $key => $value) {
            $$key = $value;
        }

        require $scriptFile;
    }

    public function render($script) {
        $scriptFile = $this->getScriptFile($script);
        $this->checkFileExists($scriptFile);

        // Set assigned vars for local script scope.
        foreach ($this->vars as $key => $value) {
            $$key = $value;
        }

        ob_start();
        require $scriptFile;
        $content = ob_get_clean();

        require $this->layoutFile;
    }

    private function checkFileExists($path) {
        if (!file_exists($path)) {
            throw new Exception("Script file does not exist: $path.");
        }
    }

    public function getScriptFile($script) {
        $scriptFile = $this->viewDirectory . self::DIRECTORY_SEPARATOR . $script . '.php';
        return $scriptFile;
    }

}
