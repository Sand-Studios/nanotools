<?php

namespace nanotools;

use Exception;

class Template {

    const DIRECTORY_SEPARATOR = '/';

    private $viewDirectory;
    private $layoutFile;

    private $vars = [];

    /**
     * Create the template with pre-set view directory and a layout file in that directory.
     * @param string $viewDirectory The directory in which to find views.
     * @param string $layoutFileName The layout script, without php extension.
     * @throws Exception When the provided paths are incorrect.
     */
    public final function __construct($viewDirectory, $layoutFileName) {
        if (empty($viewDirectory) || !file_exists($viewDirectory) || !is_dir($viewDirectory)) {
            throw new Exception("Directory does not exist: $viewDirectory.");
        }
        $this->viewDirectory = $viewDirectory;

        if (empty($layoutFileName)) {
            throw new Exception("No layout file provided.");
        }
        $layoutFile = $this->getScriptFile($layoutFileName);
        $this->checkFileExists($layoutFile);

        $this->layoutFile = $layoutFile;
    }

    /**
     * Assign a variable to be visible in view script.
     * @param string $key The variable name.
     * @param object $value The variable value.
     */
    public function assign($key, $value) {
        $this->vars[$key] = $value;
    }

    /**
     * Render a view script without layout.
     * @param string $script The script name, without php extension.
     */
    public function renderPartial($script) {
        $scriptFile = $this->getScriptFile($script);
        $this->checkFileExists($scriptFile);

        // Set assigned vars for local script scope.
        foreach ($this->vars as $key => $value) {
            $$key = $value;
        }

        require $scriptFile;
    }

    /**
     * Render a script with layout. The layout must implicitly render the script via echo $content.
     * @param string $script The script name, without php extension.
     */
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
