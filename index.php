<?php

$conf = require ('conf.php');
require ('nanotools/Autoload.php');

Autoload::import('nanotools');
Autoload::import('actions');

$template = new Template($conf['viewDirectory'], 'layout');

Router::initialize();

$indexAction = new IndexAction($template);

Router::onGet('index', $indexAction);
Router::setDefault($indexAction);

Router::run();
