<?php

/**
 * @file plugins/generic/duplicateDetector/classes/DuplicateDetectorDAO.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DuplicateDetectorDAO
 * @ingroup plugins_generic_duplicateDetector
 * @brief Operations for retrieving and modifying duplicate detectors.
 *
 */

import('plugins.generic.duplicateDetector.classes.DuplicateDetector');

class DuplicateDetectorDAO extends DAO {

	/**
	 * Constructor.
	 */
	function __construct() {
		parent::__construct();
	}

	/**
	 * Create a new data object.
	 * (allows DAO to be subclassed)
	 * @return DuplicateDetector
	 */
	function newDataObject() {
		return new DuplicateDetector();
	}

	/**
	 * Internal function to return a DuplicateDetector object from a row.
	 * @param $row array
	 * @return DuplicateDetector
	 */
	function _fromRow($row) {
		$duplicateDetector = $this->newDataObject();

		$duplicateDetector->setId($row['duplicate_detector_id']);
		$duplicateDetector->setSubmissionId($row['submission_id']);
		$duplicateDetector->setSubmissionFileId($row['submission_file_id']);
		$duplicateDetector->setLocale($row['locale']);
		$duplicateDetector->setExcerpt($row['excerpt']);
		$duplicateDetector->setAuthor($row['author']);
		$duplicateDetector->setTitle($row['title']);
		$duplicateDetector->setAbstract($row['abstract']);
		$duplicateDetector->setDateCreated($this->datetimeFromDB($row['date_created']));

		return $duplicateDetector;
	}

	/**
	 * Insert a duplicate detector.
	 * @param $duplicateDetector DuplicateDetector
	 * @return int Inserted Duplicate Detector ID
	 */
	function insertObject($duplicateDetector) {
		$this->update(
			'INSERT INTO duplicate_detectors (
					submission_id,
					submission_file_id,
					locale,
					excerpt,
					author,
					title,
					abstract,
					date_created
				) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
			array(
				$duplicateDetector->getSubmissionId(),
				$duplicateDetector->getSubmissionFileId(),
				$duplicateDetector->getLocale(),
				$duplicateDetector->getExcerpt(),
				$duplicateDetector->getAuthor(),
				$duplicateDetector->getTitle(),
				$duplicateDetector->getAbstract(),
				$duplicateDetector->getDateCreated()
			)
		);

		$duplicateDetector->setId($this->getInsertId());

		return $duplicateDetector->getId();
	}

	/**
	 * Get the ID of the last inserted duplicate detector.
	 * @return int
	 */
	function getInsertId() {
		return $this->_getInsertId('duplicate_detectors', 'duplicate_detector_id');
	}

	/**
	 * Update a duplicate detector.
	 * @param $duplicateDetector DuplicateDetector
	 * @return bool
	 */
	function updateObject($duplicateDetector) {
		return $this->update(
			'UPDATE duplicate_detectors SET
				submission_id = ?,
				submission_file_id = ?,
				locale = ?,
				excerpt = ?,
				author = ?,
				title = ?,
				abstract = ?,
				date_created = ?
			WHERE duplicate_detector_id = ?',
			array(
				$duplicateDetector->getSubmissionId(),
				$duplicateDetector->getSubmissionFileId(),
				$duplicateDetector->getLocale(),
				$duplicateDetector->getExcerpt(),
				$duplicateDetector->getAuthor(),
				$duplicateDetector->getTitle(),
				$duplicateDetector->getAbstract(),
				$duplicateDetector->getDateCreated(),
				(int) $duplicateDetector->getId()
			)
		);
	}

	/**
	 * Delete a duplicate detector.
	 * @param $duplicateDetector DuplicateDetector
	 * @return bool
	 */
	function deleteObject($duplicateDetector) {
		return $this->update(
			'DELETE FROM duplicate_detectors WHERE duplicate_detector_id = ?',
			array((int) $duplicateDetector->getId())
		);
	}

	/**
	 * Get an individual duplicate detector by ID.
	 * @param $id int DuplicateDetector ID
	 * @return DuplicateDetector
	 */
	function getById($id) {
		$result = $this->retrieve(
			'SELECT * FROM duplicate_detectors WHERE duplicate_detector_id = ?',
			array((int) $id)
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get an individual duplicate detector by Submission ID.
	 * @param $id int Submission ID
	 * @return DuplicateDetector
	 */
	function getBySubmissionId($id) {
		$result = $this->retrieve(
			'SELECT * FROM duplicate_detectors WHERE submission_id = ?',
			array((int) $id)
		);
		$row = $result->current();
		return $row ? $this->_fromRow((array) $row) : null;
	}

	/**
	 * Get all duplicate detectors.
	 * @return DAOResultFactory
	 */
	function getAll() {
		$result = $this->retrieve('SELECT * FROM duplicate_detectors ORDER BY date_created DESC');

		return new DAOResultFactory($result, $this, '_fromRow');
	}
}
