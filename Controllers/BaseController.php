<?php

class BaseController {
	public $db;
	static $instance;

	public function __construct($db) {
		$this->db = $db;
	}

	public function __get($property) {
		if (stringEndsWith($property, 'Controller')) {
			if (!property_exists($this, $property)) {
				$file = __DIR__ . '/' . ucfirst($property) . '.php';

				if (file_exists($file)) {
					include_once $file;
					$controller_name = ucfirst($property);
					$controller      = new $controller_name($this->db);

					if (is_callable(array($controller, 'init'))) {
						$controller->init();
					}

					$this->$property = $controller;
					return $this->$property;
				}
				else {
					return NULL;
				}
			}
			else {
				$this->$property = NULL;
			}
		}
		// Autoload Model
		else if (stringEndsWith($property, 'Model')) {
			if (!property_exists($this, $property)) {
				$file = __DIR__ . '/../Models/' . substr(ucfirst($property), 0, -5) . '.php';

				if (file_exists($file)) {
					include_once $file;
					$model_name = ucfirst($property);
					$model      = new $model_name($this->db);

					if (is_callable(array($model, 'init'))) {
						$model->init();
					}

					$this->$property = $model;
					return $this->$property;
				}
				else {
					return NULL;
				}
			}
			else {
				$this->$property = NULL;
			}
		}

		return $this->$property;
	}

}

