<?php
class SqliteDB {
	public  $errMsg = "";

	private $conn;

	public function __construct($db_name) {
		$this->conn = sqlite_open($db_name, 0666, $error);
		if(!$this->conn) {
			$this->errMsg = "init db error:$error";
			return false;
		}
	}

	public function insert($tb_name, $data, $fields = array(), $field_map = array()) {
		if($fields) {
			foreach($fields as $field) {
				if($field_map && isset($field_map[$field])) {
					$record[$field_map[$field]] = $data[$field];
				}
				else {
					$record[$field] = $data[$field];
				}
			}
		}
		else {
			$record = $data;
		}

		$fields = array_keys($record);

		$sql = "insert into $tb_name(" . str_replace('"', '', substr(json_encode($fields), 1, -1)) . ") values(";
		foreach($record as $v) {
			if(is_string($v)) {
				$sql .= '"' . sqlite_escape_string($v) . '",';
			}
			else {
				$sql .= $v . ',';
			}
		}

		$sql = substr($sql, 0, -1) . ')';

		$ret = sqlite_exec($this->conn, $sql);

		if($ret === false) {
			$this->errMsg = "insert db error:$sql";
			return false;
		}

		return true;
	}

	public function getRows($sql) {
		$result = sqlite_query($this->conn, $sql);

		if($result === false) {
			$this->errMsg = sqlite_last_error($this->conn);
			return false;
		}

		return sqlite_fetch_all($result, SQLITE_ASSOC);
	}

	public function remove($tb_name, $where = '') {
		$ret = sqlite_exec($this->conn, "delete from $tb_name" . ($where ? " where $where" : ''));

		if(!$ret) {
			$this->errMsg = sqlite_last_error($this->conn);
			return false;
		}

		return true;
	}

	public function update($tb_name, $data, $where, $fields = array(), $field_map = array()) {
		if($fields) {
			foreach($fields as $field) {
				if($field_map && isset($field_map[$field])) {
					$record[$field_map[$field]] = $data[$field];
				}
				else {
					$record[$field] = $data[$field];
				}
			}
		}
		else {
			$record = $data;
		}

		$fields = array_keys($record);
		
		$sql = "update $tb_name set ";
		
		foreach($record as $k => $v) {
			$sql .= "$k=";
			if(is_string($v)) {
				$sql .= '"' . sqlite_escape_string($v) . '",';
			}
			else {
				$sql .= sqlite_escape_string($v) . ',';
			}
		}

		$sql = substr($sql, 0, -1);

		if($where) {
			$sql .= ' where ' . $where;
		}

		$ret = sqlite_exec($this->conn, $sql);

		if($ret === false) {
			$this->errMsg = "insert db error:$sql";
			return false;
		}

		return true;
	}

	public function execSql($sql) {
		$ret = sqlite_exec($this->conn, $sql);
		if($ret === false) {
			$this->errMsg = 'exec sql error:' . $sql;
		}
		return $ret;
	}

	public function getInsertId() {
		return sqlite_last_insert_rowid($this->conn);
	}
}
?>