<?php

class Routes {

    private static $actionMap = [];
    private static $defaultActionName = null;
    private static $notFoundAction = null;

    private static function on($actionName, $methodName, $action) {
        if (!is_string($actionName)) {
            throw new Exception('Need to set an action name.');
        }
        if (!array_key_exists($actionName, self::$actionMap)) {
            self::$actionMap[$actionName] = [];
        }
        self::$actionMap[$actionName][$methodName] = $action;
    }

    public static function index($actionName) {
        self::$defaultActionName = $actionName;
    }

    public static function get($actionName, $action) {
        self::on($actionName, 'GET', $action);
    }

    public static function post($actionName, $action) {
        self::on($actionName, 'POST', $action);
    }

    public static function put($actionName, $action) {
        self::on($actionName, 'PUT', $action);
    }

    public static function delete($actionName, $action) {
        self::on($actionName, 'DELETE', $action);
    }

    public static function notFound($action) {
        self::$notFoundAction = $action;
    }

    public static function forward($action = null, array $params = array(), $method = 'GET', $exit = true) {
        self::run($action, $method, $params);
        if ($exit) {
            exit;
        }
    }

    public static function redirect($action = null, array $params = array()) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $params['action'] = $action;

        $header = "Location: http://$host$uri/index.php";
        if (count($params) > 0) {
            $header .= '?' . keyValueImplode('=', '&', $params);
        }
        header($header);
        exit;
    }

    public static function run($action = null, $method = null, array $params = array()) {
        $getAction = isset($_GET['action']) ? $_GET['action'] : self::$defaultActionName;
        $action = is_null($action) ? $getAction : $action;
        $method = is_null($method) ? $_SERVER['REQUEST_METHOD'] : $method;

        $actionHandler = self::route($action, $method);

        $params = array_merge($_REQUEST, $params);

        if (is_callable($actionHandler)) {
            call_user_func_array($actionHandler, $params);
        } elseif (is_callable([$actionHandler, 'run'])) {
            call_user_func_array([$actionHandler, 'tun'], $params);
        } else {
            throw new Exception('Defined action is not callable or does not have a method run()');
        }
    }

    private static function route($action, $method) {
        if (array_key_exists($action, self::$actionMap)) {
            $byAction = self::$actionMap[$action];
            if (array_key_exists($method, $byAction)) {
                return $byAction[$method];
            }
        }
        if (!is_null(self::$notFoundAction)) {
            return self::$notFoundAction;
        }
        throw new Exception('No action defined.');
    }

}

function keyValueImplode($kvGlue, $pairGlue, $array) {
    $t = [];
    foreach ($array as $key => $value) {
        $t[] = $key . $kvGlue . $value;
    }
    return implode($pairGlue, $t);
}
