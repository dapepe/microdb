<?php

namespace MicroDB;

/**
 * The Table object represents a table of a database.
 * This way you can directly work with a table instead of calling
 * all commands directly via the database object.
 *
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
		return $this->sqlLink->update($arrValues, $this->strID, $this->sqlLink->where($strKey, $strID));
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
		return $this->sqlLink->remove($this->strID, $this->sqlLink->where($strKey, $strID));
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
