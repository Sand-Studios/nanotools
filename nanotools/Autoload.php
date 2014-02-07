<?php

class Autoload {

	const DIRECTORY_SEPARATOR = '/';

	protected $_directory;

	public static final function import($directory_name){
		if (empty($directory_name)) {
			throw new Exception("Undefined directory name");
		}
		spl_autoload_register([new Autoload($directory_name), 'load']);
	}

	private final function __construct($directory_name) {
		$this->_directory = $directory_name;
	}

	public function load($classname) {
		$filename = $classname . '.php';
		$path = $this->_directory . self::DIRECTORY_SEPARATOR . $filename;
		if (file_exists($path)) {
			require($path);
		}
	}
}
