<?php

namespace MicroDB;

/**
 * The dbView object represents a view of a database.
 * This way you can directly work with a table instead of calling
 * all commands directly via the database object.
 *
 * @author Peter Haider <pepe@xilylabs.com>
 * @version 1.03
 * @package xily
 * @copyright Copyright (c) 2008, Peter Haider
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @example usage/xily.xml.use.php Some samples how to use this function
 */
class View {
	// ============== Object's core attributes ==============
	/** @var array Contains the keys for the columns */
	public $arrFields = array();
	/** @var array Contains the field preferences for the XML file (if the field should be included as attribute or subtag) */
	public $arrExclued = array();
	/** @var array Marker to add a field as subtag when generating an XML object */
	public $arrAsTag = array();
	/** @var string Name of the item tag for each object */
	public $strMainTag = "";
	/** @var array Contains the properties of each field */
	public $arrFieldProperties = array();
	/** @var string ID of the table, as assigned in the database */
	public $strID = "";
	/** @var dbConnector Pointer to database object */
	public $sqlLink = "";
	/** @var string Name of the column containing the primary key */
	public $strKey = "";


	// ============== Object's core functions ==============

	public function __construct($strID, $sqlLink) {
		$this->strID = $strID;
		$this->sqlLink = $sqlLink;
		$this->init_fields();
	}

	// ============== Item related functions ==============

	/**
	 * Returns a list with all selected fields matching the WHERE statement
	 *
	 * @param string $strWhere The SQL WHERE statement
	 * @return array|bool
	 */
	public function chart($strWhere="") {
		return $this->sqlLink->select('*', $this->strID, $strWhere);
	}

	/**
	 * Returns a complete node
	 *
	 * @param string $strKey
	 * @param string $strID
	 * @return array|bool
	 */
	public function getOneBy($strKey, $strID) {
		$arrItem = $this->sqlLink->select('*', $this->strID, $strKey.'='.$strID);
		if ($arrItem)
			return $arrItem[0];
		else
			return false;
	}

	/**
	 * Returns a complete node, identified by its primary key
	 *
	 * @param string $strID
	 * @return array|bool
	 */
	public function getOne($strID) {
		if (!$this->strKey)
			throw new Exception('There is no key defined for this table.');

		return $this->getOneBy($this->strKey, $strID);
	}

	// ============== Advanced data functions ==============

	/**
	 * Returns an xilyXML object of the table.
	 * $mxtResource can be either an array (as a result of the chart() method)
	 * or a string representing a WHERE statement to execute the chart() method.
	 *
	 * @param string|object $mxtResource Optional WHETE statement or an array containing the results of the chart() function
	 * @return xilyXML|false Returns a xilyXML object
	 */
	public function toXML($mxtResource="") {
		if (is_string($mxtResource) || !$mxtResource)
			$mxtResource = $this->chart($mxtResource);

		if (is_array($mxtResource)) {
			$xlyXML = new \Xily\Xml($this->strID);
			// Array format should be $row[0]['name']
			for ($i = 0 ; $i < sizeof($mxtResource) ; $i++) {
				$arrAttributes = array();
				$arrChildren = array();
				$z = 0;
				foreach ($mxtResource[$i] as $key => $value)
					if (!$this->arrExclued[$key]) {
						if ($this->arrAsTag[$key])
							$arrChildren[$key] = $value;
						else
							$arrAttributes[$key] = $value;
					}
				if ($this->strMainTag)
					$strItemName = $this->strMainTag;
				else
					$strItemName = 'item';
				$xlyXML->addChild(new \Xily\Xml($strItemName, '', $i, '', $arrAttributes));
				// print_r($arrChildren);
				foreach ($arrChildren as $tag => $value) {
					$xlyXML->child($i)->addChild(new \Xily\Xml($tag, '', $z, $value));
					$z++;
				}
			}
			return $xlyXML;
		} else {
			throw new Exception('Could not generate XML object: No valid data resource available');
		}
	}

	/**
	 * Adds a marker to add the field as subtag when generating an XML object.
	 *
	 * @param string $strField
	 * @return bool
	 */
	public function fieldAsTag($strField) {
		if ($this->has_field($strField)) {
			$this->arrAsTag[$strField] = true;
			return true;
		} else
			return false;
	}

	/**
	 * Adds a marker to exclude the field in any output operations (toXML and toMatrix)
	 *
	 * @param string $strField
	 * @return bool
	 */
	public function fieldExclude($strField) {
		if ($this->has_field($strField)) {
			$this->arrExclued[$strField] = true;
			return true;
		} else
			return false;
	}

	public function setTag($strTag) {
		$this->strMainTag = $strTag;
	}

	// ============== General functions ==============

	/**
	 * Sets the primary key for a view, in order to identify it's objects.
	 *
	 * @param string $strKey
	 * @return bool
	 */
	public function set_key($strKey) {
		if (in_array($strKey, $this->arrFields)) {
			$this->strKey = $strKey;
			return true;
		} else {
			$this->addError('set_key', 'There is no matching field for the desired key "'.$strKey.'". How can you expect this to work?');
			return false;
		}
	}

	/**
	 * Lists all fields of the table
	 *
	 * @return array Array containing all field names
	 */
	public function fields() {
		return $this->arrFields;
	}

	/**
	 * Initializes all fields of the table and sets the primary key
	 */
	public function init_fields() {
		$arrFields = $this->sqlLink->fields($this->strID);
		if ( !$arrFields )
			return;

		foreach ($arrFields as $field) {
			$this->arrFields[] = $field;

			// By default, add all field of the table to the field selection
			$this->arrSelection[$this->strID][] = $field;

			// Try to spot the primary key of the table
//!!! FIXME TODO
			if ( isset($this->arrFieldProperties['Key']) and
				($this->arrFieldProperties['Key'] == "PRI") )
				$this->strKey = $field;
//!!!
		}
	}

	/**
	 * Checks, if the table has a specified field
	 *
	 * @param string $strField
	 * @return bool
	 */
	public function has_field($strField) {
		return $this->sqlLink->has_field($strField, $this->strID);
	}

	/**
	 * Displays the information on the table
	 *
	 * @return string
	 */
	public function display() {
		$strDisplay  = "Table: ".$this->strID."\n";
		$strDisplay .= "= = = = = = = = = = = = = = = = = = = = = = = = = \n";
		$strDisplay .= "Database:          ".$this->sqlLink->strDatabase."\n";;
		$strDisplay .= "Primary Key:       ".$this->strKey."\n";;
		$strDisplay .= "Number of columns: ".sizeof($this->arrFields)."\n";;
		$strDisplay .= "= = = = = = = = = = = = = = = = = = = = = = = = = \n";
		$strDisplay .= "Fields: \n";
		foreach ($this->arrFields as $field)
			$strDisplay .= " - ".$field."\n";
		return $strDisplay;
	}
}
