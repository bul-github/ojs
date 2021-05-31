<?php

/**
 * @file plugins/generic/fileFormatFilter/classes/FileFilter.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileFilter
 * @ingroup plugins_generic_fileFormatFilter
 * @see FileFilter
 *
 * @brief Describes a file filter (on formats for now).
 */

// Define the filter modes.
define('FILTER_MODE_EXCLUDE', 0);
define('FILTER_MODE_INCLUDE', 1);

class FileFilter extends DataObject {

	/**
	 * Get the genre ID
	 * @return int
	 */
	function getGenreId() {
		return $this->getData('genreId');
	}

	/**
	 * Set the genre ID
	 * @param $genreId int
	 */
	function setGenreId($genreId) {
		$this->setData('genreId', $genreId);
	}

	/**
	 * Get the filter mode
	 * @return int
	 */
	function getMode() {
		return $this->getData('mode');
	}

	/**
	 * Set the filter mode
	 * @param $mode int
	 */
	function setMode($mode) {
		$this->setData('mode', $mode);
	}

	/**
	 * Get the filter value
	 * @return string
	 */
	function getFilterValue() {
		return $this->getData('filterValue');
	}

	/**
	 * Set the filter value
	 * @param $filterValue string
	 */
	function setFilterValue($filterValue) {
		$this->setData('filterValue', $filterValue);
	}

	/**
	 * Get the user ID
	 * @return int
	 */
	function getUserId() {
		return $this->getData('userId');
	}

	/**
	 * Set the user ID
	 * @param $userId int
	 */
	function setUserId($userId) {
		$this->setData('userId', $userId);
	}

	/**
	 * Get the modification date
	 * @return string
	 */
	function getDateModified() {
		return $this->getData('dateModified');
	}

	/**
	 * Set the modification date
	 * @param $dateModified string
	 */
	function setDateModified($dateModified) {
		$this->setData('dateModified', $dateModified);
	}
}
