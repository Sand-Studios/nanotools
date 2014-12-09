<?php

$conf = require('conf.php');

use nanotools\ClassLoader;
use nanotools\Container;
use nanotools\Routes;
use nanotools\Template;

require('lib/nanotools/ClassLoader.php');
ClassLoader::mount('lib/nanotools', 'nanotools'); // == mount('lib') == mount('lib', '');
ClassLoader::mount('actions');

Container::prototype('template', function () use ($conf) {
    return new Template($conf['viewDirectory'], 'layout');
});

Container::singleton('database', function () {
    $db = new PDO('sqlite:db_file.sqlite3');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $db;
});


//$db = Container::get('database');
///** @var Database $db */
//$db->exec("CREATE TABLE IF NOT EXISTS user (
//                    id INTEGER PRIMARY KEY,
//                    name TEXT,
//                    description TEXT)");
//$db->insert('user', ['name' => 'a user', 'description' => 'a description']);

Routes::get('index', new IndexAction());

Routes::get('user', function ($id) {
    var_dump(func_get_args());
    if (!is_numeric($id)) {
        $id = 1;
    }
    /** @var PDO $db */
    $db = Container::get('database');
    echo 'do the PDO query';
//    $user = $db->select('SELECT * FROM user WhERE id = :id', ['id' => $id]);
//    var_dump($user);
});

Routes::notFound(function () {
    echo '404';
});


Routes::run();
