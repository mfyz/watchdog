<?php

require_once __DIR__ . '/Lib/PHPTAL/PHPTAL.php';

class LayoutView extends \Slim\View {

	public function getPhpTalTemplateDirectory(){
		return __DIR__ . '/Views/';
	}

	public function render ($template) {
		$phptal = new \PHPTAL();
		$phptal->setTemplateRepository($this->getPhpTalTemplateDirectory());

		foreach (array_keys($this->data) as $key) {
			$phptal->set($key, $this->data[$key]);
		}

		$phptal->set('pageContent', $this->renderPageContent($template));

		print $phptal->setTemplate('Layout.phtml')->execute();
	}

	public function renderPageContent($template){
		$phptal = new \PHPTAL();
		$phptal->setTemplateRepository($this->getPhpTalTemplateDirectory());

		foreach (array_keys($this->data) as $key) {
			$phptal->set($key, $this->data[$key]);
		}

		return $phptal->setTemplate($template . '.phtml')->execute();
	}

}