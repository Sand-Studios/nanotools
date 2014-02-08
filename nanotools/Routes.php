<?php

class Routes {

    const ACTION_RUN = 'run';

    private static $actionMap = [];
    private static $defaultActionName = 'index';
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

    public static function forward($action = null, array $actionParams = array(), $method = 'GET', $exit = true) {
        self::run($action, $method, $actionParams);
        if ($exit) {
            exit;
        }
    }

    public static function redirect($action = null, array $actionParams = array()) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $actionParams['action'] = $action;

        $header = "Location: http://$host$uri/index.php";
        if (count($actionParams) > 0) {
            $header .= '?' . keyValueImplode('=', '&', $actionParams);
        }
        header($header);
        exit;
    }

    public static function run($action = null, $method = null, array $actionParams = array()) {
        $getAction = isset($_GET['action']) ? $_GET['action'] : self::$defaultActionName;
        $action = is_null($action) ? $getAction : $action;
        $method = is_null($method) ? $_SERVER['REQUEST_METHOD'] : $method;

        $actionHandler = self::route($action, $method);

        $paramPlaceholders = null;
        $userFunc = null;

        // Determine, what to run and what parameters are required. Provide parameter placeholders.
        if (is_callable($actionHandler)) {
            $paramPlaceholders = self::getDeclaredParams(new ReflectionFunction($actionHandler));
            $userFunc = $actionHandler;
        } elseif (is_callable([$actionHandler, self::ACTION_RUN])) {
            $paramPlaceholders = self::getDeclaredParams(
                                     new ReflectionMethod($actionHandler, self::ACTION_RUN));
            $userFunc = [$actionHandler, self::ACTION_RUN];
        } else {
            throw new Exception('Defined action is not callable or does not have a method run()');
        }

        $actionParams = array_merge($paramPlaceholders, $_REQUEST, $actionParams);
        call_user_func_array($userFunc, $actionParams);
    }

    private static function getDeclaredParams($reflection) {
        $paramPlaceholders = [];
        $parameters = $reflection->getParameters();
        foreach ($parameters as $p) {
            if (!$p->isOptional()) {
                $paramPlaceholders[$p->getName()] = null;
            }
        }
        return $paramPlaceholders;
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
