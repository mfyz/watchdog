<?php

class ProfiledPDO extends PDO {

	public function __construct($dsn, $username = '', $password = '', $driver_options = array())
	{
		parent::__construct($dsn, $username, $password, $driver_options);
		//$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('ProfiledPDOStatement', array($this)));
	}

	function query($statement)
	{
		$time_start = microtime(true);
		$result = parent::query($statement);
		$this->profile($time_start, $statement, 'query');
		return $result;
	}
	/*
	   //PREPARE USUALLY IS VERY FAST LETS NOT TIME IT
	function prepare($statement, $driver_options = array())
	{
		$time_start = microtime(true);
		$result = parent::prepare($statement, $driver_options);
		$this->profile($time_start, $statement, 'prepare');
		return $result;
	}
	*/

	function exec($statement)
	{
		$time_start = microtime(true);
		$r = parent::exec($statement);
		$this->profile($time_start, $statement, 'exec');
		return $r;
	}

	private function profile($time_start, $statement, $function_name)
	{
		global $application_tag;
		$time_end = microtime(true);
		$time_elapsed = $time_end - $time_start;
		$time_elapsed *= 1000; //convert to millisecs
		if (isset($application_tag) &&  $application_tag == 'tsuki') 
			$this->log_query($statement ,$function_name, $time_elapsed);
		if (isset($application_tag) &&  $application_tag == 'iPhone' && array_key_exists('log_api_queries',$GLOBALS)) 
			$this->log_query($statement ,$function_name, $time_elapsed);
		if ($time_elapsed > DB_SLOW_QUERY_LIMIT_MS)
			$this->log_slow_query($statement ,$function_name, $time_elapsed);
	}
	
	private function log_query($statement, $where, $time_elapsed)
	{
		$GLOBALS['query_log'][]= array(
						'where'        => slow_query_backtrace(),
						'statement'    => $statement,
						'phase'        => get_pdo_query_phase(),
						'time'         => (float)round($time_elapsed, 2),
						'order_number' => ++$GLOBALS['query_order_number'],
				);

	}

	private function log_slow_query($statement, $where, $time_elapsed)
	{

        //if (!isset($GLOBALS['slowQueryLogBypass'])) return;// DISABLED UNTIL FILE PERMISSIONS ARE FIXED
		if (!defined('LOG_SLOW_QUERIES_ENABLED')) return;
		if (LOG_SLOW_QUERIES_ENABLED == FALSE) return;
		if (stripos((string)$statement, 'log_slow_queries') !== FALSE) return; //avoid infinite loop

        $whereItHappened = slow_query_backtrace();
		
		$_data = json_encode(array (
            $whereItHappened,
			$where,
			$statement,
			round($time_elapsed, 2),
		));

		if (!file_exists(LOG_DIR_SLOW_QUERIES))
			@mkdir(LOG_DIR_SLOW_QUERIES, 0777, TRUE);

		$filename = LOG_DIR_SLOW_QUERIES . "/".date("YmdHis")."_".
			rand(100,990).'_'.md5(microtime(true));
		$fh = fopen($filename, 'a');
		fwrite($fh, $_data);
		fclose($fh);
	}
}


class ProfiledPDOStatement extends PDOStatement {

	protected function __construct() {
	}
	/*
	   //THESE OPERATIONS ARE USUALLY VERY FAST LETS NOT EVEN CHECK THEM
	public function fetchAll($fetch_style = null, $column_index = null, $ctor_args = null)
	{
		$time_start = microtime(true);
		$r = parent::fetchAll($fetch_style );
		$this->profile($time_start, 'fetchAll');
		return $r;
	}

	public function fetchColumn($column_number = null)
	{

		$time_start = microtime(true);
		$r = parent::fetchColumn($column_number );
		$this->profile($time_start, 'fetchColumn');
		return $r;
	}

	public function fetch($fetch_style = null, $cursor_orientation = null, $cursor_offset = null)
	{
		$time_start = microtime(true);
		$r = parent::fetch($fetch_style, $cursor_orientation, $cursor_offset);
		$this->profile($time_start, 'fetch');
		return $r;
	}

	public function fetchObject($class_name = null, $ctor_args = nullarray)
	{
		$time_start = microtime(true);
		$r = parent::fetchObject($class_name, $ctor_args);
		return $r;
	}
	*/
	public function rowCount()
	{
		$time_start = microtime(true);
		$r = parent::rowCount();
		$this->profile($time_start, 'rowCount');
		return $r;
	}

	public function execute($input_parameters = nullarray)
	{
		$time_start = microtime(true);
		$r = parent::execute($input_parameters);
		$this->profile($time_start, 'execute');
		return $r;
	}
	
	private function profile($time_start, $function_name)
	{
		global $application_tag;
		$time_end = microtime(true);
		$time_elapsed = $time_end - $time_start;
		$time_elapsed *= 1000; //convert to millisecs
		if (isset($application_tag) &&  $application_tag == 'tsuki') 
			$this->log_query($this->queryString ,$function_name, $time_elapsed);
		if (isset($application_tag) &&  $application_tag == 'iPhone' && array_key_exists('log_api_queries',$GLOBALS)) 
			$this->log_query($this->queryString ,$function_name, $time_elapsed);
		if ($time_elapsed > DB_SLOW_QUERY_LIMIT_MS)
			$this->log_slow_query($this->queryString ,$function_name, $time_elapsed);
	}

	private function log_query($statement, $where, $time_elapsed)
	{
		$GLOBALS['query_log'][]= array(
						'where'        => slow_query_backtrace(),
						'statement'    => $statement,
						'phase'        => get_pdo_query_phase(),
						'time'         => (float)round($time_elapsed, 2),
						'order_number' => ++$GLOBALS['query_order_number'],
				);

	}

	private function log_slow_query($statement, $where, $time_elapsed)
	{
		//if (!isset($GLOBALS['slowQueryLogBypass'])) return;// DISABLED UNTIL FILE PERMISSIONS ARE FIXED
		if (!defined('LOG_SLOW_QUERIES_ENABLED')) return;
		if (LOG_SLOW_QUERIES_ENABLED == FALSE) return;
		if (stripos((string)$statement, 'log_slow_queries') !== FALSE) return; //avoid infinite loop


        $whereItHappened = slow_query_backtrace();

		$_data = json_encode(
				array (
                    $whereItHappened,
					$where,
					$statement,
					round($time_elapsed, 2),
				)
		);
		$filename = LOG_DIR_SLOW_QUERIES . "/".date("YmdHis")."_".
			rand(100,990).'_'.md5(microtime(true));
		$fh = fopen($filename, 'a');
		fwrite($fh, $_data);
		fclose($fh);
	}

}


    function slow_query_backtrace() {

        $t_stack = debug_backtrace();
        array_shift( $t_stack );
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        $xh = '';
        $i = 0;
        $root_path_len = strlen(realpath(__DIR__ . '/../../'));
        foreach ( $t_stack as $t_frame ) {

            if (isset($t_frame['file'])) {
                $filename = $t_frame['file'];
            }
            else {
                $filename = NULL;
            }

            $xh .=  $filename . ':' . (isset( $t_frame['line'] ) ? $t_frame['line'] : '-') . ':' . $t_frame['function'] ;
            break;
        }


        return $xh;
    }

// this function figures out if we're in Execute, Prepare, or Fetch state
    function get_pdo_query_phase() {
        $t_stack = debug_backtrace();
        array_shift( $t_stack );
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        $pdo_function = array_shift( $t_stack ); 
		$pdo_function = $pdo_function['function'];
		return $pdo_function;
	}
/*
    function slow_query_backtraceTEST() {

        $t_stack = debug_backtrace();
        array_shift( $t_stack );
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        //$pdo_function = array_shift( $t_stack ); 
		//$pdo_function = $pdo_function['function'];
        array_shift( $t_stack ); 
        array_shift( $t_stack ); 
        $xh = '';
        $i = 0;
        $root_path_len = strlen(realpath(__DIR__ . '/../../'));
        foreach ( $t_stack as $t_frame ) {

            if (isset($t_frame['file'])) {
                $filename = $t_frame['file'];
            }
            else {
                $filename = NULL;
            }

            $xh .=  $filename . ':' . (isset( $t_frame['line'] ) ? $t_frame['line'] : '-') . ':' . $t_frame['function'] ."\n" ;
            break;
        }

		//$xh =  strtoupper($pdo_function) . ': ' . $xh;
        return $xh;
    }
*/
