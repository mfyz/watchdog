<?php

require_once __DIR__ . "/BaseController.php";

class LoggerController extends BaseController {

	public function log_exception($message, $code = 0, $file = NULL, $line = 0, $trace = array(), $type = 'logical'){
		return (bool) $this->db->insert('log', array(
			'identifier' => md5($message . $file . $line),
			'message'    => $message,
			'code'       => $code,
			'file'       => $file,
			'line'       => $line,
			'trace'      => ($trace ? json_encode($trace) : NULL),
			'created_at' => 'NOW()',
		));
	}

}

