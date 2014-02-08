<?php

$conf = require('conf.php');
require('nanotools/Import.php');

Import::directory('nanotools');
Import::directory('actions');

Container::prototype('template', function() use ($conf) {
    return new Template($conf['viewDirectory'], 'layout');
});

//Container::register('database', function() {
//    return new Database('connectionString', 'username', 'password', 'options');
//});

Routes::index('index');
Routes::get('index', new IndexAction());
Routes::notFound(function() {
    echo '404';
});

Routes::run();
