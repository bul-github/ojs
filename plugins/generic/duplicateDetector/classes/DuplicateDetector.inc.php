<?php

/**
 * @file plugins/generic/duplicateDetector/classes/DuplicateDetector.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DuplicateDetector
 * @ingroup plugins_generic_duplicateDetector
 * @brief Describes a duplicate detector queue object.
 *
 */

class DuplicateDetector extends DataObject {

	/**
	 * Get the submission ID
	 * @return int
	 */
	function getSubmissionId() {
		return $this->getData('submissionId');
	}

	/**
	 * Set the submission ID
	 * @param $submissionId int
	 */
	function setSubmissionId($submissionId) {
		$this->setData('submissionId', $submissionId);
	}

	/**
	 * Get the submission file ID
	 * @return int
	 */
	function getSubmissionFileId() {
		return $this->getData('submissionFileId');
	}

	/**
	 * Set the submission file ID
	 * @param $submissionId int
	 */
	function setSubmissionFileId($submissionFileId) {
		$this->setData('submissionFileId', $submissionFileId);
	}

	/**
	 * Get the locale
	 * @return string
	 */
	function getLocale() {
		return $this->getData('locale');
	}

	/**
	 * Set the locale
	 * @param $locale string
	 */
	function setLocale($locale) {
		$this->setData('locale', $locale);
	}

	/**
	 * Get the excerpt
	 * @return string
	 */
	function getExcerpt() {
		return $this->getData('excerpt');
	}

	/**
	 * Set the excerpt
	 * @param $excerpt string
	 */
	function setExcerpt($excerpt) {
		$this->setData('excerpt', $excerpt);
	}

	/**
	 * Get the author
	 * @return string
	 */
	function getAuthor() {
		return $this->getData('author');
	}

	/**
	 * Set the author
	 * @param $author string
	 */
	function setAuthor($author) {
		$this->setData('author', $author);
	}

	/**
	 * Get the title
	 * @return string
	 */
	function getTitle() {
		return $this->getData('title');
	}

	/**
	 * Set the title
	 * @param $title string
	 */
	function setTitle($title) {
		$this->setData('title', $title);
	}

	/**
	 * Get the abstract
	 * @return string
	 */
	function getAbstract() {
		return $this->getData('abstract');
	}

	/**
	 * Set the abstract
	 * @param $abstract string
	 */
	function setAbstract($abstract) {
		$this->setData('abstract', $abstract);
	}

	/**
	 * Get the DdMetadata for metadata comparison
	 * @return string
	 */
	function getMetadata() {
		return $this->getData('author') . " " . $this->getData('title') . " " . $this->getData('abstract');
	}

	/**
	 * Get the date created
	 * @return date
	 */
	function getDateCreated() {
		return $this->getData('dateCreated');
	}

	/**
	 * Set the date created
	 * @param $dateCreated string
	 */
	function setDateCreated($dateCreated) {
		$this->setData('dateCreated', $dateCreated);
	}

	/**
	 * Stamp the date created
	 */
	function stampDateCreated() {
		$this->setData('dateCreated', Core::getCurrentDate());
	}
}
