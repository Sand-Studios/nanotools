<?php

class Template {

	const DIRECTORY_SEPARATOR = '/';

	private $_viewDirectory;
	private $_layoutFile;

	private $_vars = [];

	public final function __construct($viewDirectory, $layoutFile) {
		if (empty($viewDirectory) || !file_exists($viewDirectory) || !is_dir($viewDirectory)) {
			throw new Exception("Directory does not exist: $viewDirectory.");
		}

		if (empty($layoutFile)) {
			throw new Ecception("No layout file provided.");
		}

		$this->_viewDirectory = $viewDirectory;
		$this->_layoutFile = $this->_viewDirectory . self::DIRECTORY_SEPARATOR . $layoutFile. '.php';

		$this->_checkFileExists($this->_layoutFile);
	}

	public function assign($key, $value) {
		$this->_vars[$key] = $value;
	}

	public function renderPartial($script) {
		$scriptFile = $this->_viewDirectory . self::DIRECTORY_SEPARATOR . $script . '.php';
		$this->_checkFileExists($scriptFile);

		// set assigned vars for local script scope
		foreach ($this->_vars as $key => $value) {
			$$key = $value;
		}

		require $scriptFile;
	}

	public function render($script) {
		$scriptFile = $this->_viewDirectory . self::DIRECTORY_SEPARATOR . $script . '.php';
		$this->_checkFileExists($scriptFile);

		// set assigned vars for local script scope
		foreach ($this->_vars as $key => $value) {
			$$key = $value;
		}

		ob_start();
		require $scriptFile;
		$content = ob_get_clean();

		require $this->_layoutFile;
	}

	private function _checkFileExists($path) {
		if (!file_exists($path)) {
			throw new Exception("Script file does not exist: $path.");
		}
	}

}
