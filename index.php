<?php

$conf = require('conf.php');
require('nanotools/Import.php');

Import::directory('nanotools');
Import::directory('actions');

Container::prototype('template', function() use ($conf) {
    return new Template($conf['viewDirectory'], 'layout');
});

//Container::singleton('database', function() {
//    return new Database('connectionString', 'username', 'password', 'options');
//});

Routes::get('index', new IndexAction());
Routes::get('user', function($user) {
   var_dump(func_get_args());
});
Routes::notFound(function() {
    echo '404';
});

Routes::run();
