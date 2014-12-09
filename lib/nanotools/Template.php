<?php

namespace nanotools;

use Exception;

class Template {

    const DIRECTORY_SEPARATOR = '/';

    private $viewDirectory;
    private $vars = [];

    /**
     * Create the template with pre-set view directory.
     * @param string $viewDirectory The directory in which to find views.
     * @throws Exception When the provided path is incorrect.
     */
    public final function __construct($viewDirectory) {
        if (!file_exists($viewDirectory) || !is_dir($viewDirectory)) {
            throw new Exception("Directory does not exist: $viewDirectory.");
        }
        $this->viewDirectory = $viewDirectory;
    }

    /**
     * Assign a variable to be visible in view script.
     * @param string $key   The variable name.
     * @param object $value The variable value.
     */
    public function assign($key, $value) {
        $this->vars[$key] = $value;
    }

    /**
     * Render a view script to the standard output.
     * @param string $script The script name, without php extension.
     */
    public function render($script) {
        $scriptFile = $this->getScriptFile($script);
        $this->checkFileExists($scriptFile);

        // Set assigned vars for local script scope.
        foreach ($this->vars as $key => $value) {
            $$key = $value;
        }

        require $scriptFile;
    }

    /**
     * Render a view script to string.
     * @param string $script The script name, without php extension.
     * @return string The rendered view.
     */
    public function renderToString($script) {
        $scriptFile = $this->getScriptFile($script);
        $this->checkFileExists($scriptFile);

        // Set assigned vars for local script scope.
        foreach ($this->vars as $key => $value) {
            $$key = $value;
        }

        ob_start();
        require $scriptFile;
        return ob_get_clean();
    }

    /**
     * Render a view script to string. Then assign it as a variable.
     * @param string $script       The script name, without php extension.
     * @param string $variableName The variable name, under which the rendered
     *                             view is assigned.
     */
    public function renderAndAssign($script, $variableName) {
        $this->assign($variableName, $this->renderToString($script));
    }

    private function checkFileExists($path) {
        if (!file_exists($path)) {
            throw new Exception("Script file does not exist: $path.");
        }
    }

    public function getScriptFile($script) {
        $scriptFile = $this->viewDirectory . self::DIRECTORY_SEPARATOR
                . $script . '.php';
        return $scriptFile;
    }

}
