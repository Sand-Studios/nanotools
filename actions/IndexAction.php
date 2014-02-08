<?php

class IndexAction {

    private $template;

    public function __construct() {
        $this->template = Container::get('template');
    }

    public function run() {
        $this->template->render('index');
    }

    public function __invoke() {
        $this->run();
    }

}
