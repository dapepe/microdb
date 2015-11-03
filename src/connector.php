<?php

namespace MicroDB;

include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'exception.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'view.php';
include_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'table.php';

/**
 * The Connector class extends the basic xily framework to work with SQL databases.
 * Please note that dbConnector is an abstract class and only provides the basic
 * functionality for database specific classes, such as dbMySQL or dbPostgreSQL class.
 * When building your own dbConnector, you would have to include the following methods:
 *
 * connect($mxtServer, $strUser="", $strPassword="", $strDatabase="")
 * reconnect()
 * disconnect()
 * query($strQuery)
 * select($mxtSelect="", $strFrom, $strWhere="", $strSortFild="", $strSortMode="ASC", $strGroupBy="")
 * join($mxtFields="", $strMainTable, $strJoinTable, $strLink)
 * remove($strTable, $strWhere)
 * update($arrValues, $strTable, $strWhere="", $bolFormatWhere=true)
 * insert($arrValues, $strTable)
 * create_table($strTable, $arrFields)
 * create_joinview($strName, $strFields, $strMainTable, $strJoinTable, $strLink)
 * drop_table($strTable)
 * drop_view($strView)
 * tables()
 * views()
 * fields($strTable)
 * keys($strTable)
 * row($resDB, [$strOutput=])
 * ping()
 * dbIdentifier() *** ABSTRACT; MUST OVERRIDE ***
 * dbString() *** ABSTRACT; MUST OVERRIDE ***
 *
 * @author Peter Haider
 *
 */
abstract class Connector {
	// ============== Object's core attributes ==============
	//
	/** @var string The database server's address */
	public $strServer = "";
	/** @var string The name of the database. */
	public $strDatabase = "";
	/** @var string The user used for the connection. */
	public $strUser = "";
	/** @var string The password of the database user. */
	public $strPassword = "";
	/** @var string The SQL link to the server. */
	public $objServer = "";
	/** @var string Array containing all table objects. */
	public $arrTables = array();
	/** @var string Array containing all view objects. */
	public $arrViews = array();


	// ============== Object's core functions ==============

	/**
	 * Returns a specified table object
	 *
	 * @param string $strTable Name of the table
	 * @return dbTable|bool
	 */
	public function table($strTable) {
		if (array_key_exists($strTable, $this->arrTables))
			return $this->arrTables[$strTable];

		return false;
	}

	/**
	 * Returns a specified view object
	 *
	 * @param string $strView Name of the view
	 * @return dbView|bool
	 */
	public function view($strView) {
		if (array_key_exists($strView, $this->arrViews))
		return $this->arrViews[$strView];
		else
		return false;
	}

	/**
	 * Checks if a specified table exists within the database
	 *
	 * @param string $strTable
	 * @return bool
	 */
	public function has_table($strTable) {
		return in_array($strTable, $this->tables());
	}

	/**
	 * Checks if a specified view exists within the database
	 *
	 * @param string $strView
	 * @return bool
	 */
	public function has_view($strView) {
		return in_array($strView, $this->views());
	}

	/**
	 * Checks if a specified field exists within a table
	 *
	 * @param string $strField
	 * @param string $strTable
	 * @return bool
	 */
	public function has_field($strField, $strTable) {
		return in_array($strField, $this->fields($strTable));
	}

	/**
	 * Initializes the dbConnector object.
	 * The function will call the init_tables() and init_view() function
	 * to create the dbTable and dbView objects.
	 */
	public function init() {
		$this->init_tables();
		$this->init_views();
	}

	/**
	 * Creates the table objects for all available tables of the database
	 */
	public function init_tables() {
		$arrTables = $this->tables();
		if ($arrTables) {
			$this->arrTables = array();
			foreach ($arrTables as $table) {
				$this->arrTables[$table] = new Table($table, $this);
			}
		}
	}

	/**
	 * Creates the view objects for all available views
	 */
	public function init_views() {
		$arrViews = $this->views();
		if ($arrViews) {
			$this->arrViews = array();
			foreach ($arrViews as $view) {
				$this->arrViews[$view] = new View($view, $this);
			}
		}
	}

	// ============== Data conversion and verification functions ==============

	/**
	 * MUST OVERRIDE. Returns a quoted identifier.
	 *
	 * @param string $identifier The identifier to quote.
	 * @return string The quoted identifier.
	 * @abstract
	 */
	abstract public function dbIdentifier($identifier);

	/**
	 * Converts the specified value according to its type.
	 * This function should be avoided if possible because it must rely on
	 * the data type PHP has assigned to the value. It is recommended to
	 * use one of the explicit functions {@link dbBool()}, {@link dbInt()},
	 * {@link dbFloat()} and {@link dbString()}.
	 *
	 * An exception will be thrown if an unsupported data type is encountered
	 * (e.g. arrays and objects), or when a conversion error occurs.
	 *
	 * @param mixed $value
	 * @param void
	 */
	public function dbParameter($value) {
		if ( is_null($value) )
			return $this->dbNull();
		elseif ( is_bool($value) )
			return $this->dbBool($value);
		elseif ( is_int($value) )
			return $this->dbInt($value);
		elseif ( is_float($value) or is_double($value) )
			return $this->dbFloat($value);
		elseif ( is_string($value) )
			return $this->dbString($value);
		else
			throw new Exception('Unsupported data type for $value.');
	}

	public function dbNull() {
		return 'NULL';
	}

	/**
	 * Returns a normalized boolean value (i.e. "1" or "0") for the database.
	 * NULL values will be returned as "NULL".
	 *
	 * @param bool $value The value to convert.
	 * @return string The normalized boolean value as a string.
	 */
	public function dbBool($value) {
		if ( is_null($value) )
			return 'NULL';
		else
			return ( $value ? '1' : '0' );
	}

	/**
	 * Returns the value if it is a valid integer; throws an exception otherwise.
	 * NULL values will be returned as "NULL".
	 *
	 * @param int $value The value to convert.
	 * @return string The integer value.
	 */
	public function dbInt($value) {
		if ( is_null($value) )
			return 'NULL';

		$value = trim($value);

		if ( !is_numeric($value) or !preg_match('/^\d+$/', (string)$value) )
			throw new Exception('$value is not an integer (value: '.print_r($value, true).').');

		return $value;
	}

	/**
	 * Returns the value if it is a valid float; throws an exception otherwise.
	 * NULL values will be returned as "NULL".
	 *
	 * @param float $value The value to convert.
	 * @return string The float value.
	 */
	public function dbFloat($value) {
		if ( is_null($value) )
			return 'NULL';

		$value = trim($value);

		if ( !is_numeric($value) )
			throw new Exception('$value is not a float (value: '.print_r($value, true).').');

		return $value;
	}

	/**
	 * MUST OVERRIDE. Returns the quoted string value, escaping all special characters.
	 * NULL values will be returned as "NULL".
	 *
	 * @param string $value The value to convert.
	 * @return string The quoted string (e.g. "abc" => "'abc'").
	 * @abstract
	 */
	abstract public function dbString($value);

	// ============== Other functions ==============

	/**
	 * Returns a "WHERE" clause if {@link $parameters} is non-empty, otherwise an empty string.
	 * For example, "$this->getWhere(array('id = 1', 'name = \'Smith\''))"
	 * would return "WHERE id = 1 AND name = 'Smith'".
	 *
	 * @param array $parameters The parameters to use in the clause.
	 * @param string $operation Optional. The operator to use in the clause.
	 *     Default is "AND".
	 * @return string Returns a "WHERE ..." expression, with the parameters
	 *     concatenated by the specified operator.
	 */
	public function getWhere(array $parameters, $operation = 'AND') {
		$expressions = implode(' '.$operation.' ', $parameters);

		if ( strlen($expressions) > 0 )
			return 'WHERE '.$expressions;
	}
}

?>
