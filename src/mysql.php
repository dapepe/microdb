<?php

namespace mdb;

/**
 * The dbMySQL class is an additional dbConnector to enable xily to work with MySQL databases.
 *
 * @author Peter Haider <pepe@xilylabs.com>
 * @version 1.03
 * @package xily
 * @copyright Copyright (c) 2008, Peter Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @example usage/xily.xml.use.php Some samples how to use this function
 *
 */

class MySQL extends Connector {
	// ============== Object's core functions ==============

	/**
	 * Fist, pass over the connection settings to the connect method
	 *
	 * @param string|resource $mxtServer Server name or a valid MySQL database linker
	 * @param string $strUser
	 * @param string $strPassword
	 * @param string $strDatabase
	 */
	public function __construct($mxtServer="", $strUser="", $strPassword="", $strDatabase="") {
		if ($mxtServer)
			$this->connect($mxtServer, $strUser, $strPassword, $strDatabase);
	}

	/**
	 * Connects to a database.
	 * You can either pass an existing database resource or leave it to the method to create the connection itself;
	 * Example 1:
	 * $myServer = mysql_connect($strServer, $strUser, $strPassword);
	 * $sqlObject = new dbMySQL($myServer);
	 * Example 2:
	 * $sqlObject = new dbMySQL("localhost", "root", "", "xilydb");
	 *
	 * @param string|resource $mxtServer Server name or a valid MySQL database linker
	 * @param string $strUser
	 * @param string $strPassword
	 * @param string $strDatabase
	 * @return bool
	 */
	public function connect($mxtServer, $strUser="", $strPassword="", $strDatabase="") {
		if (is_string($mxtServer)) {
			$this->objServer = mysql_connect($mxtServer, $strUser, $strPassword);
			if ($this->objServer) {
				$this->strServer = $mxtServer;
				$this->strUser = $strUser;
				if ($strDatabase) {
					if(mysql_select_db($strDatabase, $this->objServer)) {
						$this->strDatabase = $strDatabase;
						$this->init();
						return true;
					} else {
						$this->errorAdd("connect", "Could connect to the database, but failed to select your database \"".$strDatabase."\"... sorry!");
						return false;
					}
				} else {
					$this->errorAdd("connect", "Could connect to the database. No database specified.");
					return false;
				}
			} else {
				$this->errorAdd("connect", "Could not connect to database server!");
				return false;
			}
		} else {
			if (mysql_ping($mxtServer)) {
				$this->objServer = $mxtServer;
				return true;
			} else {
				$this->errorAdd('connect', 'The MySQL connection seems to be invalid. No ping received.');
				return false;
			}
		}
	}

	/**
	 * Reconnects to the object's database.
	 */
	public function reconnect() {
		$this->connect($this->mxtServer, $this->strUser, $this->strPassword, $this->strDatabase);
	}

	/**
	 * Disconnects from the object's database.
	 */
	public function disconnect() {
		if (mysql_close($this->objServer)) {
			unset($this->objServer);
			return true;
		} else
			return false;
	}

	// ============== Major SQL functions ==============

	/**
	 * Executes a query
	 *
	 * @param string $strQuery
	 * @return resource
	 */
	public function query($strQuery) {
		if ( !$this->ping() )
			return false;

		$resDB = mysql_query($strQuery);

		if ( $resDB !== false ) {
			return $resDB;
		} else {
			$this->errorAdd('query', 'Could not execute query "'.$strQuery.'"; Server Message: '.mysql_error());
			return false;
		}
	}

	/**
	 * Performs a selection operation and returns a 2 dim row array
	 *
	 * @param array|string $mxtSelect Field names
	 * @param string $strFrom Table name
	 * @param string $strWhere
	 * @param string $strSortFild Field name for sorting
	 * @param string $strSortMode Sorting mode (DESC, ASC)
	 * @param string $strGroupBy Group-By Statement
	 * @return array|bool
	 */
	public function select($mxtSelect="", $strFrom, $strWhere="", $strSortFild="", $strSortMode="ASC", $strGroupBy="") {
		// If $mxtSelect is an array, transform it into a comma seprarted string
		if (is_array($mxtSelect))
			$mxtSelect = implode(", ", $mxtSelect);
		if (!$mxtSelect) $mxtSelect = "*";

		$strQuery = 'SELECT '.$mxtSelect.' FROM '.$strFrom;

		if ($strWhere)
			$strQuery .= ' WHERE '.$strWhere;

		if ($strSortFild) {
			$strQuery .= ' ORDER BY '.$strSortFild;
			if ($strSortMode)
				$strQuery .= ' '.$strSortMode;
		}

		if ($strGroupBy)
			$strQuery .= ' GROUP BY '.$strGroupBy;

		$resDB = $this->query($strQuery);

		if ($resDB) {
			$arrRow = $this->row($resDB);
			mysql_free_result($resDB);
			return $arrRow;
		} else {
			$this->errorAdd('select', 'No valid result received to return a row. I did all I could but I give up.');
			return false;
		}
	}

	/**
	 * Performs an OUTER JOIN selection
	 *
	 * @param string|array $mxtFields
	 * @param string $strMainTable The name of the primary table
	 * @param string $strJoinTable The name of the secondary/join table
	 * @param string $strLink The linking/WHERE statement
	 * @return array|bool
	 */
	public function join($mxtFields="", $strMainTable, $strJoinTable, $strLink) {
		if (is_array($mxtFields))
			$mxtFields = implode(', ', $mxtFields);
		$strQuery  = 'SELECT '.$mxtFields.' FROM '.$strMainTable.' LEFT OUTER JOIN '.$strJoinTable.' ON '.$strLink;
		$resDB = $this->query($strQuery);
		if ($resDB) {
			$arrRow = $this->row($resDB);
			mysql_free_result($resDB);
			return $arrRow;
		} else {
			$this->errorAdd('select', 'No valid result received to return a row. I did all I could but I give up.');
			return false;
		}
	}

	/**
	 * Removes an item form a table
	 *
	 * @param string $strTable The name of the affected table.
	 * @param string $strWhere The WHERE statement to match the record to delete.
	 * @return bool
	 */
	public function remove($strTable, $strWhere) {
		$strQuery = 'DELETE FROM '.$strTable;
		$strQuery .= ' WHERE '.$strWhere;
		return $resultID = $this->query($strQuery);
	}

	/**
	 * Updates existing rows
	 *
	 * @param array $arrValues Associative array containing the values. The
	 *     values will be validated, quoted and escaped depending on their types.
	 * @param string $strTable The name of the affected table.
	 * @param string $strWhere Optional. The WHERE statement to match the
	 *     record to update. Default is NULL.
	 * @return bool
	 */
	public function update(array $arrValues, $strTable, $strWhere=NULL) {
		if ( count($arrValues) == 0 )
			return false;

		$strSet = '';

		foreach ($arrValues as $key => $value)
			$strSet .= $this->dbIdentifier($key).' = '.$this->dbParameter($value).',';

		$strSet = substr($strSet, 0, -1);

		$strQuery = 'UPDATE '.$strTable.' SET '.$strSet;

		if ( !is_null($strWhere) )
			$strQuery .= ' WHERE '.$strWhere;

		return $this->query($strQuery);
	}

	/**
	 * Inserts a new row into a table.
	 * The values are being passed over via an associative array, e.g. $arrValues = array("field1" => "value1", "field2" => "value2").
	 *
	 * @param array $arrValues Associative array containing the values. The
	 *     values will be validated, quoted and escaped depending on their types.
	 * @param string $strTable
	 * @return bool
	 */
	public function insert(array $arrValues, $strTable) {
		$strColumns = implode(',', array_keys($arrValues));
		$strValues = implode(',', array_map(array($this, 'dbParameter'), array_values($arrValues)));

		$strQuery = 'INSERT INTO '.$strTable.' ('.$strColumns.') VALUES ('.$strValues.')';

		return $this->query($strQuery);
	}

	/**
	 * Returns the last key added to a table
	 *
	 * @return string|bool
	 */
	public function lastKey($strTable) {
		return mysql_insert_id($this->objServer);
	}

	/**
	 * Creates a new table
	 *
	 * @param string $strTable
	 * @param array $arrFields Array containing the field names for the new table
	 * @return bool
	 */
	public function create_table($strTable, array $arrFields) {
		$strQuery  = 'CREATE VIEW article_vw AS '.$strName;
		$strQuery .= 'SELECT '.$strFields;
		$strQuery .= 'FROM '.$strMainTable.' LEFT OUTER JOIN '.$strJoinTable.' ON '.$strLink;
		return $this->query($strQuery);
	}

	/**
	 * Creates a new view
	 *
	 * @param string $strName Name of the view
	 * @param string $strFields The fields the view shall contain
	 * @param string $strMainTable The name of the primary table
	 * @param string $strJoinTable The name of the secondary/join table
	 * @param string $strLink The linking/WHERE statement
	 * @return bool
	 */
	public function create_joinview($strName, $strFields, $strMainTable, $strJoinTable, $strLink) {
		$strQuery  = 'CREATE VIEW article_vw AS '.$strName;
		$strQuery .= 'SELECT '.$strFields;
		$strQuery .= 'FROM '.$strTable.' LEFT OUTER JOIN '.$strJoinTable.' ON '.$strLink;
		return $this->query($strQuery);
	}

	/**
	 * Drops an entire table and removes the table object
	 *
	 * @param string $strTable
	 * @return bool
	 */
	public function drop_table($strTable) {
		$strQuery = 'DROP TABLE '.$strTable;
		if ($this->query($strQuery)) {
			// unset($this->table($strTable));
			return true;
		} else
			return false;
	}

	/**
	 * Drops an entire table and removes the table object
	 *
	 * @param string $strView
	 * @return bool
	 */
	public function drop_view($strView) {
		$strQuery = 'DROP VIEW '.$strView;
		if ($this->query($strQuery)) {
			// unset($this->table($strTable));
			return true;
		} else
			return false;
	}

	// ============== Assisting functions ==============

	/**
	 * Returns a list of all available tables
	 *
	 * @return array
	 */
	public function tables() {
		if ($this->ping()) {
			$arrTables = array();
			$resDB = mysql_list_tables($this->strDatabase, $this->objServer);
			while ($row = mysql_fetch_row($resDB)) {
			    $arrTables[] = $row[0];
			}
			mysql_free_result($resDB);
			return $arrTables;
		}
	}

	/**
	 * Returns a list of all available views
	 *
	 * @return array
	 */
	public function views() {
		if ($this->ping()) {
			$arrViews = array();
			$arrViewList = $this->row($this->query('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.VIEWS WHERE TABLE_SCHEMA = \''.$this->strDatabase.'\''));
			foreach ($arrViewList as $view) {
			    $arrViews[] = $view['TABLE_NAME'];
			}
			return $arrViews;
		}
	}

	/**
	 * Returns an array containing all columns of a table and its properties.
	 * The value of the field is used as array key. The other information
	 * Stored inside are: Type, Null, Key, Default, Extra
	 * Example: $arrColumns['id']['Type'] => int(7)
	 *
	 * @param string $strTable
	 * @return array|bool
	 */
	public function fields($strTable) {
		$strQuery = 'SHOW COLUMNS FROM '.$strTable;
		$resDB = $this->query($strQuery);
		if ($resDB) {
			if (mysql_num_rows($resDB) > 0) {
				$arrFields = array();
			    while ($row = mysql_fetch_assoc($resDB)) {
					$arrFields[] = $row['Field'];
			    }
			    return $arrFields;
			} else {
				$this->errorAdd('fields', 'Sorry, it seems like "'.$strTable.'" has no columns.');
				return false;
			}
		} else {
			$this->errorAdd('fields', 'No valid SQL resource received for table "'.$strTable.'". There is nothing I could do.');
			return false;
		}
	}

	/**
	 * Returns a 2 dimenstional array containing the row data
	 * e.g. $row[0]['name'], $row[1]['name'], etc.
	 *
	 * @param resource $resDB
	 * @param string $strOutput MYSQL_BOTH, MYSQL_NUM, MYSQL_ASSOC
	 * @return array|bool
	 */
	public function row($resDB, $strOutput=MYSQL_ASSOC) {
		$arrResult = array();
		while ($row = mysql_fetch_array($resDB, $strOutput)) {
			array_push($arrResult, $row);
		}
		return $arrResult;
	}

	/**
	 * Returns the last error from the previous SQL operation
	 *
	 * @return string|bool
	 */
	public function sqlerror() {
		return mysql_error($this->objServer);
	}

	/**
	 * Checks, if the connection to the server is still alive
	 *
	 * @return bool
	 */
	public function ping() {
		if ($this->objServer) {
			if (mysql_ping($this->objServer)) {
				return true;
			} else {
				$this->errorAdd('ping', 'No ping received from the server. It seems I lost the connection.');
				return false;
			}
		} else {
			$this->errorAdd('ping', 'No SQL resource available. Could not execute ping.');
			return false;
		}
	}

	/**
	 * Returns a quoted identifier.
	 *
	 * @param string $identifier The identifier to quote.
	 * @return string The quoted identifier (e.g. "id" => "`id`").
	 */
	public function dbIdentifier($identifier) {
		return '`'.str_replace('`', '``', $identifier).'`';
	}

	/**
	 * Returns the quoted string value, escaping all special characters.
	 * NULL values will be returned as "NULL".
	 *
	 * @param string $value The value to convert.
	 * @return string The quoted string (e.g. "abc" => "'abc'").
	 */
	public function dbString($value) {
		return ( is_null($value) ? 'NULL' : '\''.mysql_escape_string($value).'\'' );
	}

}

?>