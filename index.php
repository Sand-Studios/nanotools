<?php

error_reporting(E_ALL);

$conf = require('conf.php');

use nanotools\Container;
use nanotools\Database;
use nanotools\Import;
use nanotools\Routes;
use nanotools\Template;

require('lib/nanotools/Import.php');
Import::namespaced('lib'); // == Import::directory('lib/nanotools');

Import::directory('actions');

Container::prototype('template', function () use ($conf) {
    return new Template($conf['viewDirectory'], 'layout');
});

Container::singleton('database', function () {
    $db = new Database('sqlite:db_file.sqlite3');
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
    /** @var Database $db */
    $db = Container::get('database');
    $user = $db->select('SELECT * FROM user WhERE id = :id', ['id' => 1]);
    var_dump($user);
});

Routes::notFound(function () {
    echo '404';
});


Routes::run();
