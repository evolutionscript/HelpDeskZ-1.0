<?php
namespace Classes;
/**
 * @package HelpDeskZ
 * @website: http://www.helpdeskz.com
 * @community: http://community.helpdeskz.com
 * @author Evolution Script S.A.C.
 * @since 1.0.0
 */

class MySQLi
{
	var $functions = array(
		'connect'            => 'mysqli_connect',
		'connect_errno'		 => 'mysqli_connect_errno',
		'query'              => 'mysqli_query',
		'fetch_row'          => 'mysqli_fetch_row',
		'fetch_array'        => 'mysqli_fetch_array',
		'free_result'        => 'mysqli_free_result',
		'data_seek'          => 'mysqli_data_seek',
		'error'              => 'mysqli_error',
		'errno'              => 'mysqli_errno',
		'affected_rows'      => 'mysqli_affected_rows',
		'num_rows'           => 'mysqli_num_rows',
		'num_fields'         => 'mysqli_num_fields',
		'field_name'         => 'mysqli_field_name',
		'insert_id'          => 'mysqli_insert_id',
		'real_escape_string' => 'mysqli_real_escape_string',
		'close'              => 'mysqli_close',
		'client_encoding'    => 'mysqli_client_encoding',
	);

	var $registry = null;
	var $fetchtypes = array(
		DBARRAY_NUM   => MYSQLI_NUM,
		DBARRAY_ASSOC => MYSQLI_ASSOC,
		DBARRAY_BOTH  => MYSQLI_BOTH
	);
	var $database = null;
	
	function connect($db_name, $db_server, $db_user, $db_passwd, $db_prefix){
		$this->tbl_prefix = $db_prefix;
		$this->database = $db_name;
		$this->connection_master = $this->db_connect($db_name, $db_server, $db_user, $db_passwd);
	}
    function testconnect($db_name, $db_server, $db_user, $db_passwd){
        $this->connection_master = @$this->functions[connect]($db_server, $db_user, $db_passwd, $db_name);
        if(!$this->connection_master){
            return("<strong>Error MySQLi DB Connection</strong>. Please contact to site administrator.");
        }elseif($db_name == ''){
            return("<strong>Error MySQLi DB Connection</strong>. Please contact to site administrator.");
        }
    }
	function db_connect($db_name, $db_server, $db_user, $db_passwd){
		$link = @$this->functions[connect]($db_server, $db_user, $db_passwd, $db_name);
		if ($this->functions['connect_errno']()){
			die("<br /><br /><strong>Error MySQLi DB Conection</strong><br>Please contact to site administrator.");
		}
		return $link;
	}

	function close()
	{
		return @$this->functions['close']($this->connection_master);
	}
	function query($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_master);
	}
	function &execute_query($buffered = true, &$link)
	{
		$this->connection_recent =& $link;
		$this->querycount++;

		if ($queryresult = $this->functions[$buffered ? 'query' : 'query_unbuffered']($link, $this->sql))
		{
			// unset $sql to lower memory .. this isn't an error, so it's not needed
			$this->sql = '';

			return $queryresult;
		}
		else
		{
			// unset $sql to lower memory .. error will have already been thrown
			$this->sql = '';
		}
	}
	function &fetchRow($sql, $type = DBARRAY_ASSOC)
	{
		$this->sql =& $sql;
		$queryresult = $this->execute_query(true, $this->connection_master);
		$returnarray = $this->fetch_array($queryresult, $type);
		$this->free_result($queryresult);
		return $returnarray;
	}
	function fetch_array($queryresult, $type =DBARRAY_ASSOC)
	{
		$result = @$this->functions['fetch_array']($queryresult, $this->fetchtypes["$type"]);
		return $result;
	}
	# Fetch One
	# Devuelve el resultado de un query
	function fetchOne($sql)
	{
		$var = $this->fetchRow($sql, DBARRAY_NUM);
		return $var[0];
	}
	# insert
	# Inserta la data en una tabla, los datos debe ser array
	function insert($tbl, $dataArray)
	{
		foreach($dataArray as $k=>$v){
			$keys .= "$k, ";
			$values .= "'".$this->real_escape_string($v)."', ";
		}

		$keys = substr ($keys, 0, strlen($keys) - 2);
		$values = substr ($values, 0, strlen($values) - 2);
		$sql = "INSERT INTO {$tbl}($keys) VALUES({$values})";
		$exeq = $this->query($sql);
		return $exeq;
	}
	# InsertID
	# Deveulve el ultimo id insertado
	function lastInsertId()
	{
		return @$this->functions['insert_id']($this->connection_master);
	}
	# Delete
	# Elimina uno o varios datos de la tabla
	function delete($tbl, $data=null)
	{
		if($data != ''){
			$conditional = "WHERE {$data}";
		}
		$sql = "DELETE FROM {$tbl} {$conditional}";
		$this->query($sql);
	}
	# Update
	# Actualiza uno o varios campos
	function update($tbl, $dataArray, $conditional=null)
	{
		foreach($dataArray as $k=>$v){
			$updsql .= "$k='".$this->real_escape_string($v)."', ";
		}
		$updsql = substr ($updsql, 0, strlen($updsql) - 2);
		if($conditional != ''){
			$updsql.= "WHERE {$conditional}";
		}
		$sql = "UPDATE {$tbl} SET {$updsql}";
		$this->query($sql);
	}

	function free_result($queryresult)
	{
		$this->sql = '';
		return @$this->functions['free_result']($queryresult);
	}
	function real_escape_string($string)
	{
		$this->sql = '';
		return @$this->functions['real_escape_string']($this->connection_master,$string);
	}
}