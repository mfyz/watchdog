<?php

if (!defined('DB_SLOW_QUERY_LIMIT_MS')) define('DB_SLOW_QUERY_LIMIT_MS', 1000);

require_once dirname(__FILE__). "/ProfiledPDO.php";

Class ArchetypePDO extends ProfiledPDO {
    function __construct($dsn, $username="", $password="", 
		$driver_options = array()) {
        parent::__construct($dsn, $username, $password, $driver_options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, 
            array('ArchetypePDOStatement', array()));
    }
    
    function query($statement) {
        $result = parent::query($statement);
        if ($result === FALSE) {            
            $_err = $this->errorInfo();
            $msg = $_err[2] . PHP_EOL;
            $msg .= ' --- ' . $statement;
            throw new Exception($msg);
        }
        return $result;
    }

    function prepare($statement, $driver_options = array()) {
        $result = parent::prepare($statement, $driver_options);
        if ($result === FALSE) {                        
            $_err = $this->errorInfo();
            $msg = $_err[2] . PHP_EOL;
            $msg .= ' --- ' . $statement;
            throw new Exception($msg);
        }
        return $result;
    }
    
    function exec($statement) {
        $r = parent::exec($statement);
        if ($r === FALSE) {
            $_err = $this->errorInfo();
            $msg = $_err[2] . PHP_EOL;
            $msg .= ' --- ' . $statement;
            throw new Exception($msg);
        }
        return $r;
    }

    function specialSqlValue($value){
        return (strtolower($value) == 'now()' || 
            strtolower($value) == 'null' || 
            strtolower($value) == 'true' || 
            strtolower($value) == 'false');
    }

    function insert($table, $_data, $ignore = FALSE){
        if (empty($_data)) {
            throw new Exception("ArchetypePDO::insert() --- empty $_data");
        }

        // Find fields and data.
        $_keys = array();
        $_values = array();
        $_value_quotes = array();
        foreach ($_data as $key => $value) {
            $_keys[] = $key;
            $_values[] = $value;

            if ($this->specialSqlValue($value)) {
                $_value_quotes[] = $value;
            } else {
                $_value_quotes[] = '?';
            }
        }

        // build sql
        $sql = "INSERT " . ($ignore ? 'IGNORE' : '') . " INTO $table (`" .
			implode("`, `", $_keys) . 
			"`) VALUES (" . 
			// implode(',', array_fill(0, count($_values), '?')) .
            implode(", ", $_value_quotes) .
			')';

        $preparedQuery = $this->prepare($sql);

        // add data
        $i = 1;
        foreach ($_values as $value) {
            if (!$this->specialSqlValue($value)) {
                $preparedQuery->bindValue($i, $value);
                $i++;
            }
        }

        // Execute the insert query.
        return $preparedQuery->execute();
    }

    function insertDelayed($table, $_data, $ignore = FALSE){
        if (empty($_data)) {
            throw new Exception("ArchetypePDO::insert() --- empty $_data");
        }

        // Find fields and data.
        $_keys = array();
        $_values = array();
        $_value_quotes = array();
        foreach ($_data as $key => $value) {
            $_keys[] = $key;
            $_values[] = $value;

            if ($this->specialSqlValue($value)) {
                $_value_quotes[] = $value;
            } else {
                $_value_quotes[] = '?';
            }
        }

        // build sql
        $sql = "INSERT DELAYED " . ($ignore ? 'IGNORE' : '') . " INTO $table (`" .
			implode("`, `", $_keys) . 
			"`) VALUES (" . 
			// implode(',', array_fill(0, count($_values), '?')) .
            implode(", ", $_value_quotes) .
			')';

        $preparedQuery = $this->prepare($sql);

        // add data
        $i = 1;
        foreach ($_values as $value) {
            if (!$this->specialSqlValue($value)) {
                $preparedQuery->bindValue($i, $value);
                $i++;
            }
        }

        // Execute the insert query.
        return $preparedQuery->execute();
    }

    function insert_id() {
        return $this->lastInsertId();
    }

	/**
	 * @param string $table
	 * @param bool $where
	 * @param bool $select
	 * @param bool $limit
	 * @param bool $sqlMode
	 * @return array
	 */
    function getAll($table, $where = FALSE, $limit = FALSE, $order = FALSE) {
	    // Base sql
	    $sql = "SELECT *
	        FROM `$table`";

	    // Preparing where keys and values arrays
	    $sql_where_array = $sql_where_values = array();
	    if ($where) {
		    if (count($where) > 0) {
			    foreach ($where as $key => $value) {
				    $sql_where_array[]  = "`$key` = ?";
				    $sql_where_values[] = $value;
			    }
		    }
	    }

	    // Adding keys to sql
	    if (count($sql_where_array) > 0) {
		    $sql .= " WHERE " . implode(' AND ', $sql_where_array);
	    }

	    // Adding order if exists
	    if (is_array($order)) {
		    $sql .= " ORDER BY ";
		    foreach ($order as $key => $ord) {
			    $sql .= " `$key` " . ($ord == 'DESC' ? 'DESC' : 'ASC');
		    }
	    }

	    // Adding limit if exists
	    if (is_array($limit)) {
		    $sql .= " LIMIT " . (int)$limit[0] . ', ' . (int)$limit[1];
	    }

	    // Preparing query
	    $query = $this->prepare($sql);

	    // Binding values
	    if (count($sql_where_values) > 0) {
		    $i = 1;
		    foreach ($sql_where_values as $value) {
			    $query->bindValue($i, $value);
			    $i++;
		    }
	    }

	    // Running query
	    $query->execute();

	    // Return all results as array
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    function getAllWithSql($sql) {
	    $query = $this->prepare($sql);
	    $query->execute();
	    // Return all results as array
	    return $query->fetchAll(PDO::FETCH_ASSOC);
    }

    function getRow($table, $where = FALSE) {
	    $queryResult = $this->getAll($table, $where, array(0, 1));
	    if (!$queryResult) return FALSE;
	    return $queryResult[0];
    }

	function getRowWithSql($sql){
		$query = $this->prepare($sql);
		$query->execute();
		return $query->fetch(PDO::FETCH_ASSOC);
	}

    function getCell($select, $table, $where = FALSE) {
	    // Base sql
	    $sql = "SELECT $select
	        FROM `$table`";

	    // Preparing where keys and values arrays
	    $sql_where_array = $sql_where_values = array();
	    if ($where) {
		    if (count($where) > 0) {
			    foreach ($where as $key => $value) {
				    $sql_where_array[]  = "`$key` = ?";
				    $sql_where_values[] = $value;
			    }
		    }
	    }

	    // Adding keys to sql
	    if (count($sql_where_array) > 0) {
		    $sql .= " WHERE " . implode(' AND ', $sql_where_array);
	    }

	    // Preparing query
	    $query = $this->prepare($sql);

	    // Binding values
	    if (count($sql_where_values) > 0) {
		    $i = 1;
		    foreach ($sql_where_values as $value) {
			    $query->bindValue($i, $value);
			    $i++;
		    }
	    }

	    // Running query
	    $query->execute();
	    $result = $query->fetch(PDO::FETCH_NUM);

	    // Return first
	    return $result[0];
    }

	function getCellWithSql($sql) {
		// Preparing query
		$query = $this->prepare($sql);

		// Running query
		$query->execute();
		$result = $query->fetch(PDO::FETCH_NUM);

		// Return first
		return $result[0];
	}

    function delete($table, $where) {
	    // Base sql
	    $sql = "DELETE FROM `$table`";

	    // Preparing where keys and values arrays
	    $sql_where_array = $sql_where_values = array();
	    if ($where) {
		    if (count($where) > 0) {
			    foreach ($where as $key => $value) {
				    $sql_where_array[]  = "`$key` = ?";
				    $sql_where_values[] = $value;
			    }
		    }
	    }

	    // Adding keys to sql
	    if (count($sql_where_array) > 0) {
		    $sql .= " WHERE " . implode(' AND ', $sql_where_array);
	    }

	    // Preparing query
	    $query = $this->prepare($sql);

	    // Binding values
	    if (count($sql_where_values) > 0) {
		    $i = 1;
		    foreach ($sql_where_values as $value) {
			    $query->bindValue($i, $value);
			    $i++;
		    }
	    }

	    // Return query result
	    return $query->execute() ? TRUE : FALSE;
    }

    function update($table, $_data, $where = NULL,
		$returnAffectedRowCount = FALSE){
		
        if (!is_array($_data)) return FALSE;

        // Finding fields and data
        $_values = $_update = array();
        foreach ($_data as $key => $value) {
            $_values[] = $value;

            if ($this->specialSqlValue($value)) {
                $_update[] = '`' . $key . '` = ' . $value;
            } else {
                $_update[] = '`' . $key . '` = ?';
            }
        }

        // Building base sql.
        $sql = "UPDATE $table SET ". implode(", ", $_update);

	    // Preparing where keys and values arrays
        $sql_where_array = $sql_where_values = array();
        if( $where ){
			if (count($where) > 0) {
				foreach ($where as $key => $value) {
					$sql_where_array[]  = "`$key` = ?";
					$sql_where_values[] = $value;
				}
			}

	        // Adding keys to sql
	        if (count($sql_where_array) > 0) {
		        $sql .= " WHERE " . implode(' AND ', $sql_where_array);
	        }
        }

	    // Run SQL
        $preparedQuery = $this->prepare($sql);

        // Binding update values
        $i = 1;
        foreach ($_values as $value) {
            if (!$this->specialSqlValue($value)) {
                $preparedQuery->bindValue($i, $value);
                $i++;
            }
        }

	    // Binding where clause values
        if (count($sql_where_values) > 0) {
	        foreach ($sql_where_values as $value) {
		        $preparedQuery->bindValue($i, $value);
		        $i++;
	        }
        }

        // Execute the UPDATE statement.
		if ($returnAffectedRowCount) {
			$preparedQuery->execute();
			$rows_affected = $preparedQuery->rowCount();
			return $rows_affected;
		}
		else {
			return $preparedQuery->execute();
		}
    }
}

Class ArchetypePDOStatement extends ProfiledPDOStatement {
    // Without this, the PDO::ATTR_STATEMENT_CLASS won't work.
    protected function __construct() {
		// Default to arrays indexed by column name.
		self::setFetchMode(PDO::FETCH_ASSOC);
    }   


    function execute($input_parameters = NULL) {
        $r = parent::execute($input_parameters);
        if ($r === FALSE) {
            $_err = $this->errorInfo();
            $msg = $_err[2]. PHP_EOL;
            $msg .= ' --- ' . $input_parameters ;
            throw new Exception($msg);
        }
        return $r; 
    }

}

function db_connect($host, $database, $username, $password, $port = NULL) {
    $dsn = "mysql:host=$host";

	if ($port) {
		$dsn .= ";port=" . $port;
	}

	$dsn .= ";dbname=$database";

    try {
        $dbh = @new ArchetypePDO($dsn, $username, $password);
    }
    catch (Exception $e) {
        //$dbh = NULL;
	    var_dump($e);
	    exit;
		throw $e;
    }
	return $dbh;	    
}
