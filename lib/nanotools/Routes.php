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

    /**
     * @var Request The cached request data.
     */
    private static $request = null;

    /**
     * Define the default (index) action.
     * @param string $actionName The index action.
     */
    public static function index($actionName) {
        self::$defaultActionName = $actionName;
    }

    /**
     * Define an action handler for HTTP GET.
     * @param string $actionName The action name.
     * @param mixed $actionHandler The Callable or class implementing run().
     */
    public static function get($actionName, $actionHandler) {
        self::register($actionName, self::GET, $actionHandler);
    }

    /**
     * Define an action handler for HTTP POST.
     * @param string $actionName The action name.
     * @param mixed $actionHandler The Callable or class implementing run().
     */
    public static function post($actionName, $actionHandler) {
        self::register($actionName, self::POST, $actionHandler);
    }

    /**
     * Define an action handler for HTTP PUT.
     * @param string $actionName The action name.
     * @param mixed $actionHandler The Callable or class implementing run().
     */
    public static function put($actionName, $actionHandler) {
        self::register($actionName, self::PUT, $actionHandler);
    }

    /**
     * Define an action handler for HTTP DELETE.
     * @param string $actionName The action name.
     * @param mixed $actionHandler The Callable or class implementing run().
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
     * @param string $actionName The action name.
     * @param array $requestParameters Additional request parameters. Will override existing ones.
     * @param bool $exit Whether to exit after the execution returns.
     */
    public static function forward($actionName, array $requestParameters = array(), $exit = true) {
        self::runInternal($actionName, Routes::GET, $requestParameters);
        if ($exit) {
            exit;
        }
    }

    /**
     * Initiate a roundtrip making the client request the action via HTTP GET.
     * @param string $actionName The action name.
     * @param array $requestParameters Request parameters.
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
     * Get the request data wrapper. Useful for accessing vars, that are not in $_GET or $_POST.
     * @return Request The request wrapper.
     */
    public static function requestData() {
        self::initRequest();
        return self::$request;
    }

    /**
     * Bootstrap and run an action based on current http request.
     */
    public static function run() {
        self::runInternal();
    }

    private static function runInternal($actionName = null, $methodName = null, array $parameters = []) {
        self::initRequest();
        $requestActionName = isset($_GET['action']) ? $_GET['action'] : self::$defaultActionName;
        $actionName = is_null($actionName) ? $requestActionName : $actionName;
        $methodName = is_null($methodName) ? self::$request->getMethod() : $methodName;

        $actionHandler = self::route($actionName, $methodName);
        list($callback, $placeholders) = self::getCallbackAndPlaceholders($actionHandler);
        $parameters = empty($placeholders) ? $parameters :
                array_merge($placeholders, self::$request->getData(), $parameters);

        call_user_func_array($callback, $parameters);
    }

    private static function initRequest() {
        if (is_null(self::$request)) {
            self::$request = new Request();
        }
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

class Request {

    private $method;
    private $data;

    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->data = [
                Routes::GET => null,
                Routes::POST => null,
                Routes::PUT => null,
                Routes::DELETE => null
        ];
    }

    /**
     * Get the current HTTP method.
     * @return string HTTP request method.
     */
    public function getMethod() {
        return $this->method;
    }

    /**
     * Get the request data for current or any HTTP method.
     * @param string $method HTTP method. If null, current method is used.
     * @return array Request data. GET parameters are always included, but overwritten if their
     * name is in conflict with other parameter.
     */
    public function getData($method = null) {
        if ($method == null) {
            $method = $this->method;
        }
        if (is_null($this->data[$method])) {
            $this->data[$method] = $this->getRequestData($method);
        }
        return $this->data[$this->method];
    }

    private function getRequestData($method) {
        // GET parameters are always overwritten by others, when in conflict.
        switch ($method) {
            case Routes::GET:
                return $_GET;
            case Routes::POST:
                return array_merge($_GET, $_POST);
            case Routes::PUT: // Fallthrough.
            case Routes::DELETE:
                $requestData = [];
                parse_str(file_get_contents("php://input"), $requestData);
                return array_merge($_GET, $requestData);
            default:
                return []; // Other request methods not supported yet.
        }
    }

}
