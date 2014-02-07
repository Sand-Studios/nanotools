<?php

class Router {

	private static $_method;
	private static $_action;

	private static $_defaultAction;

	private static $_actions = [];

	public static function initialize(){
		self::$_method = $_SERVER['REQUEST_METHOD'];
		self::$_action = isset($_GET['action']) ? $_GET['action'] : 'index';
	}

	private static function on($actionName, $methodName, Action $action) {
		if (empty($actionName) || !is_string($actionName)) {
			throw new Exception("Need to set an action name.");
		}
		if (!isset(self::$_actions[$actionName])) {
			self::$_actions[$actionName] = [];
		}
		self::$_actions[$actionName][$methodName] = $action;
	}

	public static function setDefault(Action $action) {
		self::$_defaultAction = $action;
	}

	public static function onGet($actionName, Action $action) {
		self::on($actionName, 'GET', $action);
	}

	public static function onPost($actionName, Action $action) {
		self::on($actionName, 'POST', $action);
	}

	public static function onPut($actionName, Action $action) {
		self::on($actionName, 'PUT', $action);
	}

	public static function onDelete($actionName, Action $action) {
		self::on($actionName, 'DELETE', $action);
	}

	public static function forward($target = null, $method = 'GET', $exit = true) {
		self::$_action = $target;
		self::$_method = $method;
		run();
		if ($exit) {
			exit;
		}
	}

	public static function redirect($target = null, array $params = array()) {
		$host = $_SERVER['HTTP_HOST'];
		$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
		$params['action'] = $target;

		$header = "Location: http://$host$uri/index.php";
		if (count($params) > 0) {
			$header .= "?" . keyValueImplode('=','&',$params);
		}
		header($header);
		exit;
	}

	public static function run() {
		if (is_null(self::$_action)) {
			if (!isset(self::$_defaultAction)) {
				throw new Exception("Undefined default action.");
			}
			self::$_defaultAction->run();
		}
		if (!isset(self::$_actions[self::$_action])
				|| !isset(self::$_actions[self::$_action][self::$_method])) {
			throw new Exception("Undefined action.");
			// TODO: 404 here
		}
		self::$_actions[self::$_action][self::$_method]->run();
	}
}

function keyValueImplode($glue1, $glue2, $array) {
	$t = [];
	foreach ($array as $key => $value) {
		$t[] = $key . $glue1 . $value;
	}
	return implode($glue2, $t);
}
