<?php

class db {

	public $dbId = false;
	public $queryNum = 0;
	public $queryList = array();
	public $queryErrorsList = array();
	public $mysqlError = '';
	public $mysqlVersion = '';
	public $mysqlErrorNum = 0;
	public $mysqlExtend = '';
	public $mySQLTimeTaken = 0;
	public $queryId = false;

	function __destruct() {
		if( $this->dbId ) mysqli_close($this->dbId);
		$this->dbId = false;
	}

	public function connect($db_user, $db_pass, $db_name, $db_location = 'localhost', $show_error=1) {
		$db_location = explode(":", $db_location);
		
		$time_before = $this->getRealTime();
		
		mysqli_report(MYSQLI_REPORT_OFF);

		if (isset($db_location[1])) {
			$this->dbId = mysqli_connect($db_location[0], $db_user, $db_pass, $db_name, $db_location[1]);
		} else {
			$this->dbId = mysqli_connect($db_location[0], $db_user, $db_pass, $db_name);
		}
		
		$this->queryList[] = array('query' => 'Connection with MySQL Server',
									'time'  => ($this->getRealTime() - $time_before),
									'num'   => 0);
		
		if(!$this->dbId) {
			if($show_error == 1) {
				$this->displayError(mysqli_connect_error(), '1');
			} else {
				$this->queryErrorsList[] = array( 'error' => mysqli_connect_error() );
				return false;
			}
		}

		$res = $this->superQuery( "SELECT VERSION() AS `version`", false, false, false );

		$this->mysqlVersion = $res['version'];

		if( version_compare($this->mysqlVersion, '5.5.3', '<') ) {
			die ("Required MySQL version 5.5.3 or greater. You need upgrade MySQL version on your server.");
		}

		mysqli_set_charset($this->dbId , COLLATE );
		
		mysqli_query($this->dbId, "SET NAMES '" . COLLATE . "'", false );

		$this->sqlMode();

		return true;
	}
	
	public function query($query, $show_error=true, $log_query=true) {
		$time_before = $this->getRealTime();

		if(!$this->dbId) $this->connect(DB_USER, DB_PASS, DB_NAME, DB_HOST);

		if(!($this->queryId = mysqli_query($this->dbId, $query) )) {

			$this->mysqlError = mysqli_error($this->dbId);
			$this->mysqlErrorNum = mysqli_errno($this->dbId);

			if($show_error) {
				
				$this->displayError($this->mysqlError, $this->mysqlErrorNum, $query);
				
			} else {
				
				$this->queryErrorsList[] = array( 'query' => $query, 'error' => $this->mysqlError );
				
			}
		}
			
		$this->mySQLTimeTaken += $this->getRealTime() - $time_before;

	    if( $log_query ) {
			
			$this->queryList[] = array('query' => $query,
										'time'  => ($this->getRealTime() - $time_before), 
										'num'   => count($this->queryList));
			$this->queryNum ++;
			
		}
		
		return $this->queryId;
	}
	
	public function getRow($queryId = '') {
		if ($queryId == '') $queryId = $this->queryId;

		return mysqli_fetch_assoc($queryId);
	}

	private function getRealTime() {
		list($seconds, $microSeconds) = explode(' ', microtime());
		return ((float)$seconds + (float)$microSeconds);
	}
	
	public function superQuery($query, $multi = false, $show_error=true, $log_query=true) {

		if(!$multi) {

			$this->query($query, $show_error, $log_query);
			$data = $this->getRow();
			$this->free();
			
			return $data;

		} else {
			
			$this->query($query, $show_error, $log_query);
			
			$rows = array();
			
			while($row = $this->getRow()) {
				$rows[] = $row;
			}

			$this->free();

			return $rows;
		}
	}
	
	public function numRows($queryId = '') {
		if ($queryId == '') $queryId = $this->queryId;

		return mysqli_num_rows($queryId);
	}
	
	public function insertId() {
		return mysqli_insert_id($this->dbId);
	}

	public function safesql( $source ) {
		if(!$this->dbId) $this->connect(DB_USER, DB_PASS, DB_NAME, DB_HOST);

		if ($this->dbId) return mysqli_real_escape_string ($this->dbId, $source);
		else return addslashes($source);
	}

	public function free( $queryId = '' ) {

		if ($queryId == '') $queryId = $this->queryId;

		if ( $queryId ) {
			mysqli_free_result($queryId);
			$this->queryId = false;
		}
	}

	public function close() {
		if( $this->dbId )  mysqli_close($this->dbId);
		$this->dbId = false;
	}

	private function sqlMode() {
		$remove_modes = array( 'STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES', 'ONLY_FULL_GROUP_BY', 'NO_ZERO_DATE', 'NO_ZERO_IN_DATE', 'TRADITIONAL' );
		
		$res = $this->query( "SELECT @@SESSION.sql_mode", false, false );

		$row = $this->getArray();
		
		if ( !$row[0] ) {
			return;
		}
		
		$modes_array = explode( ',', $row[0] );
		$modes_array = array_change_key_case( $modes_array, CASE_UPPER );

		foreach ( $modes_array as $key => $value ) {
			if ( in_array( $value, $remove_modes ) ) {
				unset( $modes_array[ $key ] );
			}
		}
		
		$mode_list = implode(',', $modes_array);

		if($row[0] != $mode_list) {
			$this->query( "SET SESSION sql_mode='{$mode_list}'", false, false );
		}
		
	}

	public function getArray($query_id = '') {
		if ($query_id == '') $query_id = $this->queryId;

		return mysqli_fetch_array($query_id);
	}

	private function displayError($error, $error_num, $query = '') {

		$query = htmlspecialchars($query, ENT_QUOTES, 'utf-8');
		$error = htmlspecialchars($error, ENT_QUOTES, 'utf-8');

		$trace = debug_backtrace();

		$level = 0;
		if (isset($trace[1]['function']) AND $trace[1]['function'] == "query" ) $level = 1;
		if (isset($trace[1]['function']) AND $trace[2]['function'] == "super_query" ) $level = 2;

		$trace[$level]['file'] = str_replace(ROOT_DIR, "", $trace[$level]['file']);
		
		die("<b>MySQL error</b> in file: <b>{$trace[$level]['file']}</b> at line <b>{$trace[$level]['line']}</b>The Error returned was:<br /> <b>{$error}</b><br /><b>SQL query:</b><br /><br />{$query}");
	}

}
