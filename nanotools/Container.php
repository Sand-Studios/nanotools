<?php

class Container {

    const SINGLETON = 'singleton';
    const PROTOTYPE = 'prototype';

    /**
     * The closures creating the components.
     */
    private static $initializers = [];

    /**
     * The singleton instance cache. If a key exists here, the component is a singleton.
     */
    private static $singletons = [];

    /**
     * Register a new component initializer under a name with prototype scope.
     * @param string $name The registered name.
     * @param Callable $initializer The Callable that creates the component.
     * @throws Exception When the name is already in use.
     */
    public static function prototype($name, Callable $initializer) {
        self::register($name, $initializer, self::PROTOTYPE);
    }

    /**
     * Register a new component initializer under a name with singleton scope.
     * @param string $name The registered name.
     * @param Callable $initializer The Callable that creates the component.
     * @throws Exception When the name is already in use.
     */
    public static function singleton($name, Callable $initializer) {
        self::register($name, $initializer, self::SINGLETON);
    }

    /**
     * Get the component for a name.
     * @param string $name The name.
     * @return object The instance.
     * @throws Exception When nothing is found.
     */
    public static function get($name) {
        if (!self::registered($name)) {
            throw new Exception('No component under that name.');
        }
        $initializer = self::$initializers[$name];
        if (array_key_exists($name, self::$singletons)) {
            // Cache, if singleton.
            if (is_null(self::$singletons[$name])) {
                self::$singletons[$name] = $initializer();
            }
            return self::$singletons[$name];
        }
        return $initializer();
    }

    /**
     * Determine whether the name is registered.
     * @param string $name The id.
     * @return bool Whether to id exists or not.
     */
    public static function registered($name) {
        return array_key_exists($name, self::$initializers);
    }

    private static function register($name, Callable $initializer, $scope) {
        if (self::registered($name)) {
            throw new Exception("The name: $name is already in use");
        }
        self::$initializers[$name] = $initializer;
        if ($scope == self::SINGLETON) {
            self::$singletons[$name] = null;
        }
    }

}
