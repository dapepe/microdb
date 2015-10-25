<?php

namespace MicroDB;

/**
 * The dbTable object represents a table of a database.
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
class Table extends View {

	// ============== Data manipulation ==============
	/**
	 * Inserts a new item
	 *
	 * @param array $arrValues Assoc. Array containing the values to be inserted
	 */
	public function insert($arrValues) {
		$this->sqlLink->insert($arrValues, $this->strID);
	}

	/**
	 * Updates a specified item
	 *
	 * @param string $strKey The selector
	 * @param string $strID Key of the object
	 * @param array $arrValues Assoc. Array containing the values to be updated
	 * @return bool
	 */
	public function updateBy($strKey, $strID, $arrValues) {
		return $this->sqlLink->update($this->strID, $arrValues, $strKey."=".$strID);
	}

	/**
	 * Updates a specified item
	 *
	 * @param string $strID Key of the object
	 * @param array $arrValues Assoc. Array containing the values to be updated
	 * @return bool
	 */
	public function update($strID, $arrValues) {
		return $this->updateBy($this->strKey, $strID, $arrValues);
	}

	/**
	 * Removes a specified item
	 *
	 * @param string $strKey The selector
	 * @param string $strID Key of the object
	 * @return bool
	 */
	public function removeBy($strKey, $strID) {
		return $this->sqlLink->remove($this->strID, $strKey."=".$strID);
	}

	/**
	 * Removes a specified item
	 *
	 * @param string $strID Key of the object
	 * @return bool
	 */
	public function remove($strID) {
		return $this->removeBy($this->strKey, $strID);
	}
}
