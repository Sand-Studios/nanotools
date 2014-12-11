<?php

$conf = require('conf.php');

use nanotools\ClassLoader;
use nanotools\Container;
use nanotools\Routes;
use nanotools\Template;

require('lib/nanotools/ClassLoader.php');
ClassLoader::mount('lib/nanotools', 'nanotools'); // == mount('lib') == mount('lib', '');
ClassLoader::mount('actions');

$container = new Container();

// -- Simple things to test the container.

// Simple registered component.
$container->singleton('a', function () {
    return 'a';
});

// Registered component with automatically injected dependencies.
$container->singleton('b', function ($a) {
    return 'b' . $a;
});

// Registered component with missing dependencies. Will throw error on get()
$container->singleton('d', function ($c) {
    return 'd' . $c;
});

// Registered component with missing but defaulted dependencies. Is ok.
$container->singleton('f', function ($e = 'e') {
    return 'f' . $e;
});


// -- Some more realistic components.

// Template renderer, no dependencies.
$container->prototype('template', function () use ($conf) {
    return new Template($conf['viewDirectory']);
});

// Database connection. No dependencies.
$container->singleton('database', function () {
    $db = new PDO('sqlite:db_file.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});

// Action handler. Template is injected.
$container->prototype('index_action', function ($template) {
    return new IndexAction($template);
});


//$db = Container::get('database');
///** @var Database $db */
//$db->exec("CREATE TABLE IF NOT EXISTS user (
//                    id INTEGER PRIMARY KEY,
//                    name TEXT,
//                    description TEXT)");
//$db->insert('user', ['name' => 'a user', 'description' => 'a description']);

Routes::get('index', $container->get('index_action'));

Routes::get('user', function ($id) use ($container) {
    var_dump(func_get_args());
    if (!is_numeric($id)) {
        $id = 1;
    }
    /** @var PDO $db */
    $db = $container->get('database');
    echo 'do the PDO query';
});

Routes::get('container', function () use ($container) {
    echo '<p>' . $container->get('b') . '</p>';
    try {
        echo '<p>' . $container->get('d') . '</p>';
    } catch (Exception $exception) {
        echo 'Container exception: ' . $exception->getMessage();
    }
    echo '<p>' . $container->get('f') . '</p>';
});

Routes::notFound(function () {
    echo '404';
});


Routes::run();
