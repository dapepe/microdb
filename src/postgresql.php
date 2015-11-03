<?php

namespace MicroDB;

/**
 * The dbPostgreSQL class is an additional dbConnector to enable xily to work with PostgreSQL databases.
 *
 * @author Peter Haider <pepe@xilylabs.com>
 * @version 1.03
 * @package xily
 * @copyright Copyright (c) 2008, Peter Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @example usage/xily.xml.use.php Some samples how to use this function
 *
 */

class PostgreSQL extends Connector {
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
		// Fist, pass over the connection settings to the connect method
		if ($mxtServer)
			$this->connect($mxtServer, $strUser, $strPassword, $strDatabase);
	}

	/**
	 * Connects to a database.
	 * You can either pass an existing database resource or leave it to the method to create the connection itself;
	 * Example 1:
	 * $myServer = pg_connect($strConnect);
	 * $sqlObject = new dbPostgreSQL($myServer);
	 * Example 2:
	 * $sqlObject = new dbPostgreSQL("localhost", "root", "", "xilydb");
	 *
	 * @param string|resource $mxtServer Server name or a valid MySQL database linker
	 * @param string $strUser
	 * @param string $strPassword
	 * @param string $strDatabase
	 * @return PostgreSQL
	 */
	public function connect($mxtServer, $strUser="", $strPassword="", $strDatabase="") {
		if (is_string($mxtServer)) {
			$arrServer = explode(':', $mxtServer);
			if (sizeof($arrServer) > 1) {
				$strServer = $arrServer[0];
				$strPort = $arrServer[1];
			} else {
				$strPort = 5432;
			}
			$strConnect = 'host='.$mxtServer.' port='.$strPort.' user='.$strUser.' password='.$strPassword.' dbname='.$strDatabase;
			$this->objServer = \pg_connect($strConnect);
			if (!$this->objServer)
				throw new Exception('Cound not connect to database!');
		} else {
			if (!\pg_ping($mxtServer))
				throw new Exception('The PostgreSQL connection seems to be invalid. No ping received.');

			$this->objServer = $mxtServer;
		}

		$db->init();
		return $this;
	}

	/**
	 * Reconnects to the object's database.
	 */
	public function reconnect() {
		return $this->connect($this->mxtServer, $this->strUser, $this->strPassword, $this->strDatabase);
	}

	/**
	 * Disconnects from the object's database.
	 */
	public function disconnect() {
		if (\pg_close($this->objServer)) {
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
		if ($this->ping()) {
			$resDB = \pg_query($strQuery);
			if (!$resDB)
			   throw new Exception('Could not execute query "'.$strQuery.'"; Server Message: '.\pg_last_error());

			return $resDB;
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
			\pg_free_result($resDB);
			return $arrRow;
		}

		throw new Exception('No valid result received to return a row. I did all I could but I give up.');
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
	public function joinLeft($mxtFields="", $strMainTable, $strJoinTable, $strLink) {
		$strQuery  = 'SELECT '.$mxtFields.' FROM '.$strMainTable.' LEFT OUTER JOIN '.$strJoinTable.' ON '.$strLink;
		return $this->query($strQuery);
	}

	/**
	 * Removes an item form a table
	 *
	 * @param string $strTable
	 * @param string $strWhere
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
	 * Creates a new table
	 *
	 * @param string $strTable
	 * @param array $arrFields Array containing the field names for the new table
	 * @return bool
	 */
	public function create_table($strTable, $arrFields) {
		$strQuery = '';
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
		$strQuery  = 'CREATE VIEW '.$strName.' AS';
		$strQuery .= ' SELECT '.$strFields;
		$strQuery .= ' FROM '.$strMainTable.' LEFT OUTER JOIN '.$strJoinTable.' ON ('.$strLink.')';
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
			$resDB = $this->query("SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'r' ORDER BY relname");;
			while ($row = \pg_fetch_row($resDB)) {
			    $arrTables[] = $row[0];
			}
			\pg_free_result($resDB);
			return $arrTables;
		}
	}

	/**
	 * Returns a list of all available views
	 *
	 * @return array
	 */
	public function views() {
	// relkind
		if ($this->ping()) {
			$arrViews = array();
			$resDB = $this->query("SELECT relname FROM pg_class WHERE relname !~ '^(pg_|sql_)' AND relkind = 'v' ORDER BY relname");
			while ($row = \pg_fetch_row($resDB)) {
			    $arrViews[] = $row[0];
			}
			\pg_free_result($resDB);
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
		$strQuery = 'SELECT a.attname FROM pg_attribute a, pg_class c, pg_type t WHERE c.relname = \''.$strTable.'\' AND a.attnum > 0 AND a.attrelid = c.oid AND a.atttypid = t.oid';
		$resDB = $this->query($strQuery);
		if (!$resDB)
			throw new Exception('No valid SQL resource received for table "'.$strTable.'". There is nothing I could do.');

		if (\pg_num_rows($resDB) == 0)
			throw new Exception('Sorry, it seems like "'.$strTable.'" has no columns.');

		$arrFields = array();
	    while ($row = \pg_fetch_assoc($resDB)) {
			$arrFields[] = $row['attname'];
	    }
	    return $arrFields;
	}

	/**
	 * Returns a 2 dimenstional array containing the row data
	 * e.g. $row[0]['name'], $row[1]['name'], etc.
	 *
	 * @param resource $resDB
	 * @param string $strOutput MYSQL_BOTH, MYSQL_NUM, MYSQL_ASSOC
	 * @return array|bool
	 */
	public function row($resDB, $strOutput=PGSQL_ASSOC) {
		$arrResult = array();
		while ($row = \pg_fetch_array($resDB, $strOutput)) {
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
		return \pg_last_error($this->objServer);
	}

	/**
	 * Checks, if the connection to the server is still alive
	 *
	 * @return bool
	 */
	public function ping() {
	// Checks, if the connection to the server is still alive
		if (!$this->objServer)
			throw new Exception('No SQL resource available. Could not execute ping.');

		return \pg_ping($this->objServer);
	}

	/**
	 * Returns a quoted identifier.
	 *
	 * @param string $identifier The identifier to quote.
	 * @return string The quoted identifier (e.g. 'id' => '"id"').
	 */
	public function dbIdentifier($identifier) {
		return '"'.str_replace('"', '""', $identifier).'"';
	}

	/**
	 * Returns the quoted string value, escaping all special characters.
	 * NULL values will be returned as "NULL".
	 *
	 * @param string $value The value to convert.
	 * @return string The quoted string (e.g. "abc" => "'abc'").
	 */
	public function dbString($value) {
		return ( is_null($value) ? 'NULL' : '\''.\pg_escape_string($value).'\'' );
	}

	public function where($strKey, $strID, $strOperator='=') {
		return \pg_escape_string($strKey).$strOperator.$this->dbString($strID);
	}

	public function whereLike($strKey, $strID) {
		return $this->where($strKey, $strID, ' LIKE ');
	}
}

?>
