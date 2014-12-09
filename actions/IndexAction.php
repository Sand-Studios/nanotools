<?php

use nanotools\Container;

class IndexAction {

    private $template;

    public function __construct() {
        $this->template = Container::get('template');
    }

    public function __invoke() {
        $this->template->assign('args', func_get_args());
        $this->template->renderAndAssign('index', 'content');
        $this->template->render('layout');
    }

}
