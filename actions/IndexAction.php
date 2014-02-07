<?php

class IndexAction implements Action {

	private $template;

	public function __construct($template) {
		$this->template = $template;
	}

	public function run() {
		$this->template->render('index');
	}

}
