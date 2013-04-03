<?php

class BaseModel {
	public $db;

	function __construct ($db) {
		$this->db = $db;
	}

	public function __get ($property) {
		if (stringEndsWith($property, 'Model')) {
			if (!property_exists($this, $property)) {
				$file = __DIR__ . '/' . substr(ucfirst($property), 0, -5) . '.php';

				if (file_exists($file)) {
					include_once $file;
					$model_name = ucfirst($property);
					$model = new $model_name($this->db, $this->ds, $this->luna);

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
