<?php

namespace nanotools;

use Exception;
use ReflectionFunction;

class Container {

    const SINGLETON = 'singleton';
    const PROTOTYPE = 'prototype';

    /**
     * The closures creating the components.
     */
    private $initializers = [];

    /**
     * The singleton instance cache. If a key exists here, the component is a
     * singleton.
     */
    private $singletons = [];

    /**
     * Register a new component initializer under a name with prototype scope.
     * @param string   $name        The registered name.
     * @param callable $initializer The callable that creates the component.
     *                              May contain parameters which will be
     *                              injected if possible.
     * @throws Exception When the name is already in use.
     */
    public function prototype($name, callable $initializer) {
        $this->register($name, $initializer, self::PROTOTYPE);
    }

    /**
     * Register a new component initializer under a name with singleton scope.
     * @param string   $name        The registered name.
     * @param callable $initializer The callable that creates the component.
     *                              May contain parameters which will be
     *                              injected if possible.
     * @throws Exception When the name is already in use.
     */
    public function singleton($name, callable $initializer) {
        $this->register($name, $initializer, self::SINGLETON);
    }

    /**
     * Get the component for a name.
     * @param string $name The name.
     * @return object The instance.
     * @throws Exception When component cannot be returned. Either it is not
     *                     registered, or it depends on another component, that
     *                     cannot be injected.
     */
    public function get($name) {
        if (!$this->registered($name)) {
            throw new Exception('No component under that name.');
        }
        $initializer = $this->initializers[$name];
        $reflectionFunction = new ReflectionFunction($initializer);
        $parameters = [];

        foreach ($reflectionFunction->getParameters() as $dependency) {
            $dependencyName = $dependency->getName();
            if (!$this->registered($dependencyName)) {
                // Allow default value to be filled.
                // In this case, isDefaultValueAvailable() equiv. isOptional()
                if (!$dependency->isDefaultValueAvailable()) {
                    throw new Exception("Cannot instantiate $name:
                        Unsatisfied dependency: $dependencyName");
                }
            } else {
                $parameters[] = $this->get($dependencyName);
            }
        }

        // Check if not already created.
        if (array_key_exists($name, $this->singletons)) {
            if (is_null($this->singletons[$name])) {
                $this->singletons[$name] = $initializer(...$parameters);
            }
            return $this->singletons[$name];
        }
        return $initializer(...$parameters);
    }

    /**
     * Determine whether the name is registered.
     * @param string $name The id.
     * @return bool Whether to id exists or not.
     */
    public function registered($name) {
        return array_key_exists($name, $this->initializers);
    }

    private function register($name, callable $initializer, $scope) {
        if ($this->registered($name)) {
            throw new Exception("The name: $name is already in use");
        }
        $this->initializers[$name] = $initializer;
        if ($scope == self::SINGLETON) {
            $this->singletons[$name] = null;
        }
    }

}
