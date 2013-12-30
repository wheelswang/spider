<?php
/**
 * created by wheelswang@2013-12-15 01:13
 * version 1.0
 */
class Sqlite3DB {
	public  $errMsg = "";

	private $db = null;

	public function __construct($db_name) {
		try {
			$this->db = new SQLite3($db_name);
		}
		catch(Exception $e) {
			$this->errMsg = $e->getCode() . '|' . $e->getMessage();
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
				$sql .= '"' . $this->db->escapeString($v) . '",';
			}
			else {
				$sql .= $v . ',';
			}
		}

		$sql = substr($sql, 0, -1) . ')';

		$ret = $this->db->exec($sql);

		if($ret === false) {
			$this->errMsg = $this->db->lastErrorCode() . '|' . $this->db->lastErrorMsg() . " sql:$sql";
			return false;
		}

		return true;
	}
	public function getRows($sql) {
		$result = $this->db->query($sql);

		if($result === false) {
			$this->errMsg = $this->db->lastErrorCode() . '|' . $this->db->lastErrorMsg() . " sql:$sql";
			return false;
		}

		$rows = array();
		while($row = $result->fetchArray()) {
			$rows[] = $row;
		}

		return $rows;
	}

	public function remove($tb_name, $where = '') {
		$sql = "delete from $tb_name" . ($where ? " where $where" : '');
		$ret = $this->db->exec($sql);

		if(!$ret) {
			$this->errMsg = $this->db->lastErrorCode() . '|' . $this->db->lastErrorMsg() . " sql:$sql";
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
				$sql .= '"' . $this->db->escapeString($v) . '",';
			}
			else {
				$sql .= $this->db->escapeString($v) . ',';
			}
		}

		$sql = substr($sql, 0, -1);

		if($where) {
			$sql .= ' where ' . $where;
		}

		$ret = $this->db->exec($sql);

		if($ret === false) {
			$this->errMsg = $this->db->lastErrorCode() . '|' . $this->db->lastErrorMsg() . " sql:$sql";
			return false;
		}

		return true;
	}

	public function execSql($sql) {
		$ret = $this->db->exec($sql);

		if($ret === false) {
			$this->errMsg = $this->db->lastErrorCode() . '|' . $this->db->lastErrorMsg() . " sql:$sql";
			return false;
		}

		return true;
	}

	public function getInsertId() {
		return $this->db->lastInsertRowID();
	}
}