<?php

class Routes {

    const ACTION_RUN = 'run';

    const GET = 'GET';
    const POST = 'POST';
    const PUT = 'PUT';
    const DELETE = 'DELETE';

    private static $actionMap = [];
    private static $defaultActionName = 'index';
    private static $notFoundAction = null;

    /**
     * @var Request The cached request data.
     */
    private static $request = null;

    public static function index($actionName) {
        self::$defaultActionName = $actionName;
    }

    public static function get($actionName, $action) {
        self::on($actionName, self::GET, $action);
    }

    public static function post($actionName, $action) {
        self::on($actionName, self::POST, $action);
    }

    public static function put($actionName, $action) {
        self::on($actionName, self::PUT, $action);
    }

    public static function delete($actionName, $action) {
        self::on($actionName, self::DELETE, $action);
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
            $header .= '?' . self::keyValueImplode('=', '&', $actionParams);
        }
        header($header);
        exit;
    }

    /**
     * @return Request
     */
    public static function requestData() {
        self::initRequest();
        return self::$request;
    }

    public static function run($action = null, $method = null, array $parameters = array()) {
        self::initRequest();
        $getAction = isset($_GET['action']) ? $_GET['action'] : self::$defaultActionName;
        $action = is_null($action) ? $getAction : $action;
        $method = is_null($method) ? self::$request->getMethod() : $method;

        $actionHandler = self::route($action, $method);
        list($callback, $placeholders) = self::getCallbackAndPlaceholders($actionHandler);

        $parameters = array_merge($placeholders, self::$request->getData(), $parameters);
        call_user_func_array($callback, $parameters);
    }

    private static function initRequest() {
        if (is_null(self::$request)) {
            self::$request = new Request();
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

    private static function on($actionName, $methodName, $action) {
        if (!is_string($actionName)) {
            throw new Exception('Need to set an action name.');
        }
        if (!array_key_exists($actionName, self::$actionMap)) {
            self::$actionMap[$actionName] = [];
        }
        self::$actionMap[$actionName][$methodName] = $action;
    }

    private static function getCallbackAndPlaceholders($actionHandler) {
        list($callback, $reflector) = self::getCallbackAndReflector($actionHandler);

        $placeholders = [];
        $parameters = $reflector->getParameters();
        foreach ($parameters as $p) {
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
        if (is_callable([$actionHandler, self::ACTION_RUN])) {
            $callback = [$actionHandler, self::ACTION_RUN];
            $reflector = new ReflectionMethod($actionHandler, self::ACTION_RUN);
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

    public function getMethod() {
        return $this->method;
    }

    public function getData($method = null) {
        if ($method == null) {
            $method = $this->method;
        }
        if (is_null($this->data[$method])) {
            $this->data[$method] = $this->initData($method);
        }
        return $this->data[$this->method];
    }

    private function initData($method) {
        switch ($method) {
            case Routes::GET:
                return $_GET;
            case Routes::POST:
                return $_POST;
            case Routes::PUT: // Fallthrough.
            case Routes::DELETE:
                $requestData = [];
                parse_str(file_get_contents("php://input"), $requestData);
                return $requestData;
            default:
                return []; // Other request methods not supported yet.
        }
    }

}
