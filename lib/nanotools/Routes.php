<?php

namespace nanotools;

use Exception;
use ReflectionFunction;
use ReflectionMethod;

class Routes {

    const ACTION_RUN_METHOD = 'run';

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    private static $handlers = [];
    private static $defaultActionName = 'index';
    private static $notFoundHandler = null;

    private static $requestBody = null;
    private static $requestData = [
            self::GET    => null,
            self::POST   => null,
            self::PUT    => null,
            self::DELETE => null
    ];

    /**
     * Define the default (index) action.
     * @param string $actionName The index action.
     */
    public static function index($actionName) {
        self::$defaultActionName = $actionName;
    }

    /**
     * Define an action handler for HTTP GET.
     * @param string $actionName    The action name.
     * @param mixed  $actionHandler The Callable or class implementing run().
     */
    public static function get($actionName, $actionHandler) {
        self::register($actionName, self::GET, $actionHandler);
    }

    /**
     * Define an action handler for HTTP POST.
     * @param string $actionName    The action name.
     * @param mixed  $actionHandler The Callable or class implementing run().
     */
    public static function post($actionName, $actionHandler) {
        self::register($actionName, self::POST, $actionHandler);
    }

    /**
     * Define an action handler for HTTP PUT.
     * @param string $actionName    The action name.
     * @param mixed  $actionHandler The Callable or class implementing run().
     */
    public static function put($actionName, $actionHandler) {
        self::register($actionName, self::PUT, $actionHandler);
    }

    /**
     * Define an action handler for HTTP DELETE.
     * @param string $actionName    The action name.
     * @param mixed  $actionHandler The Callable or class implementing run().
     */
    public static function delete($actionName, $actionHandler) {
        self::register($actionName, self::DELETE, $actionHandler);
    }

    /**
     * Define an action handler for resource not found.
     * @param mixed $actionHandler The Callable or class implementing run().
     */
    public static function notFound($actionHandler) {
        self::$notFoundHandler = $actionHandler;
    }

    /**
     * Bootstrap and run another action handler. Will be routed for HTTP GET.
     * @param string $actionName        The action name.
     * @param array  $requestParameters Additional request parameters. Will override existing ones.
     * @param bool   $exit              Whether to exit after the execution returns.
     */
    public static function forward($actionName, array $requestParameters = [], $exit = true) {
        self::runInternal($actionName, Routes::GET, $requestParameters);
        if ($exit) {
            exit;
        }
    }

    /**
     * Initiate a round-trip making the client request the action via HTTP GET.
     * @param string $actionName        The action name.
     * @param array  $requestParameters Request parameters.
     */
    public static function redirect($actionName, array $requestParameters = []) {
        $host = $_SERVER['HTTP_HOST'];
        $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        $requestParameters['action'] = $actionName;

        $header = "Location: http://$host$uri/index.php"; // TODO: what about rewritten urls?
        if (count($requestParameters) > 0) {
            $header .= '?' . self::keyValueImplode('=', '&', $requestParameters);
        }
        header($header);
        exit;
    }

    /**
     * Bootstrap and run an action based on current http request.
     */
    public static function run() {
        $actionName = isset($_GET['action']) ? $_GET['action'] : self::$defaultActionName;
        $methodName = $_SERVER['REQUEST_METHOD'];
        self::runInternal($actionName, $methodName);
    }

    /**
     * Get request data for the current request. Both url and request body parameters are returned.
     * @return array The request data as associative array.
     */
    public static function getRequestParameters() {
        $method = $_SERVER['REQUEST_METHOD'];
        if (is_null(self::$requestData[$method])) {
            self::$requestData[$method] = self::findRequestParameters($method);
        }
        return self::$requestData[$method];
    }

    private static function runInternal($actionName, $methodName, array $parameters = []) {
        $actionHandler = self::route($actionName, $methodName);
        list($callback, $placeholders) = self::getCallbackAndPlaceholders($actionHandler);

        // Only fill request data if the handler needs it.
        $parameters = empty($placeholders) ? $parameters :
                array_merge($placeholders, self::getRequestParameters(), $parameters);

        call_user_func_array($callback, $parameters);
    }

    private static function findRequestParameters($method) {
        // GET parameters are always overwritten by others, when in conflict.
        switch ($method) {
            case Routes::GET:
                return $_GET;
            case Routes::POST:
                return array_merge($_GET, $_POST);
            case Routes::PUT: // Fallthrough.
            case Routes::DELETE:
                $requestData = self::getRequestBody();
                return array_merge($_GET, $requestData);
            default:
                return []; // Other request methods not supported yet.
        }
    }

    private static function getRequestBody() {
        if (is_null(self::$requestBody)) {
            self::$requestBody = [];
            parse_str(file_get_contents("php://input"), self::$requestBody);
        }
        return self::$requestBody;
    }

    private static function route($actionName, $methodName) {
        if (array_key_exists($actionName, self::$handlers)) {
            $byAction = self::$handlers[$actionName];
            if (array_key_exists($methodName, $byAction)) {
                return $byAction[$methodName];
            }
        }
        if (!is_null(self::$notFoundHandler)) {
            return self::$notFoundHandler;
        }
        throw new Exception('No action defined.');
    }

    private static function register($actionName, $methodName, $actionHandler) {
        if (!is_string($actionName)) {
            throw new Exception('Need to set an action name.');
        }
        if (!array_key_exists($actionName, self::$handlers)) {
            self::$handlers[$actionName] = [];
        }
        self::$handlers[$actionName][$methodName] = $actionHandler;
    }

    private static function getCallbackAndPlaceholders($actionHandler) {
        list($callback, $reflector) = self::getCallbackAndReflector($actionHandler);

        $placeholders = [];
        foreach ($reflector->getParameters() as $p) {
            if (!$p->isOptional()) {
                $placeholders[$p->getName()] = null;
            }
        }
        return [$callback, $placeholders];
    }

    private static function getCallbackAndReflector($actionHandler) {
        if (is_callable($actionHandler)) {
            $reflector = new ReflectionFunction($actionHandler);
            return [$actionHandler, $reflector];
        }
        // TODO: Support just callables?
        if (is_callable([$actionHandler, self::ACTION_RUN_METHOD])) {
            $callback = [$actionHandler, self::ACTION_RUN_METHOD];
            $reflector = new ReflectionMethod($actionHandler, self::ACTION_RUN_METHOD);
            return [$callback, $reflector];
        }
        throw new Exception('Defined action is not callable or does not have a method run()');
    }

    private static function keyValueImplode($kvGlue, $pairGlue, $array) {
        $t = [];
        foreach ($array as $key => $value) {
            $t[] = $key . $kvGlue . $value;
        }
        return implode($pairGlue, $t);
    }

}
