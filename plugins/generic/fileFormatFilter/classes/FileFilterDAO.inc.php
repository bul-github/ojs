<?php

/**
 * @file plugins/generic/fileFormatFilter/classes/FileFilterDAO.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileFilterDAO
 * @ingroup plugins_generic_fileFormatFilter
 * @see FileFilterDAO
 *
 * @brief Operations for retrieving and modifying the file filters.
 */

import('plugins.generic.fileFormatFilter.classes.FileFilter');

class FileFilterDAO extends DAO {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Create new data object (allows DAO to be subclassed).
	 * @return FileFilter
	 */
	function newDataObject() {
		return new FileFilter();
	}

	/**
	 * Internal function to return a FileFilter object from a row.
	 * @param $row array
	 * @return FileFilter
	 */
	function _returnFromRow($row) {
		$fileFilter = $this->newDataObject();
		$fileFilter->setId($row['file_filter_id']);
		$fileFilter->setGenreId($row['genre_id']);
		$fileFilter->setMode($row['mode']);
		$fileFilter->setFilterValue($row['filter_value']);
		$fileFilter->setUserId($row['user_id']);
		$fileFilter->setDateModified($this->datetimeFromDB($row['date_modified']));
		return $fileFilter;
	}

	/**
	 * Insert a file filter.
	 * @param $fileFilter FileFilter
	 * @return int Inserted file filter ID
	 */
	function insertObject($fileFilter) {
		$this->update(
			'INSERT INTO file_filters (
					genre_id,
					mode,
					filter_value,
					user_id,
					date_modified
				) VALUES (?, ?, ?, ?, ?)',
			array(
				$fileFilter->getGenreId(),
				$fileFilter->getMode(),
				$fileFilter->getFilterValue(),
				$fileFilter->getUserId(),
				$fileFilter->getDateModified()
			)
		);
		$fileFilter->setId($this->getInsertId());
		return $fileFilter->getId();
	}

	/**
	 * Get the ID of the last inserted file filter.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('file_filters', 'file_filter_id');
	}

	/**
	 * Update a file filter.
	 * @param $fileFilter FileFilter
	 * @return bool
	 */
	function updateObject($fileFilter) {
		return $this->update(
			'UPDATE file_filters SET
				genre_id = ?,
				mode = ?,
				filter_value = ?,
				user_id = ?,
				date_modified = ?
			WHERE file_filter_id = ?',
			array(
				$fileFilter->getGenreId(),
				$fileFilter->getMode(),
				$fileFilter->getFilterValue(),
				$fileFilter->getUserId(),
				$fileFilter->getDateModified(),
				(int) $fileFilter->getId()
			)
		);
	}

	/**
	 * Get an individual file filter by ID.
	 * @param $id int FileFilter ID
	 * @return FileFilter
	 */
	function getById($id) {
		$params = array(
			(int) $id
		);
		$result = $this->retrieve(
			'SELECT *
			FROM file_filters
			WHERE file_filter_id = ?',
			$params
		);
		$row = (array) $result->current();
		return $row ? $this->_returnFromRow($row) : null;
	}

	/**
	 * Get an individual file filter.
	 * @param $genreId int The article genre ID
	 * @return FileFilter
	 */
	function getByGenreId($genreId) {
		$params = array(
			(int) $genreId
		);
		$result = $this->retrieve(
			'SELECT *
			FROM file_filters
			WHERE genre_id = ?',
			$params
		);
		$row = (array) $result->current();
		return $row ? $this->_returnFromRow($row) : null;
	}
}
