<?php

/**
 * @file plugins/generic/duplicateDetector/DuplicateDetectorPlugin.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DuplicateDetectorPlugin
 * @ingroup plugins_generic_duplicateDetector
 * @brief Main file for DuplicateDetector plugin.
 *
 */

use Smalot\PdfParser\Parser;

import('lib.pkp.classes.plugins.GenericPlugin');
import('plugins.generic.duplicateDetector.classes.DuplicateDetectorDAO');

require_once('classes/DocConversion.php');

include('config.inc');

class DuplicateDetectorPlugin extends GenericPlugin {
	/**
	 * Register the plugin to properly link the methods.
	 * @param $category
	 * @param $path
	 * @param null $mainContextId
	 * @return bool
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled()) {
				$duplicateDetectorDAO = new DuplicateDetectorDAO();

				DAORegistry::registerDAO('DuplicateDetectorDAO', $duplicateDetectorDAO);

				HookRegistry::register('SubmissionFile::edit', array(&$this, 'executeForFile'));
				HookRegistry::register('submissionfilesuploadform::execute', array(&$this, 'executeForFile'));
				HookRegistry::register('PKPSubmissionSubmitStep3Form::execute', array(&$this, 'executeForSubmission'));
				HookRegistry::register('AcronPlugin::parseCronTab', array(&$this, 'parseCronTab'));
			}
			return true;
		}
		return false;
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.duplicateDetector.name');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.duplicateDetector.description');
	}

	function executeForFile($hookName, $args) {
		$isSubmissionFilesUploadForm = $hookName === "submissionfilesuploadform::execute";

		if ($isSubmissionFilesUploadForm) {
			$submissionFile =& $args[1];
		}
		else {
			$submissionFile =& $args[0];
		}

		if (!$submissionFile || $submissionFile->getFileStage() != SUBMISSION_FILE_SUBMISSION) {
			return $submissionFile;
		}

		$genreId = $submissionFile->getGenreId();
		$genreDao = DAORegistry::getDAO('GenreDAO');
		$genre = $genreDao->getById($genreId);

		if (!$genre || $genre->getKey() !== 'SUBMISSION') {
			return $submissionFile;
		}

		$submissionFileDAO = DAORegistry::getDAO('SubmissionFileDAO');
		$submissionFileId = $submissionFile->getId();
		$revisions = $submissionFileDAO->getRevisions($submissionFileId);
		$isFirstRevision = $revisions->count() === 1;

		if (!$isFirstRevision) {
			return $submissionFile;
		}

		$publicFilesDir = Config::getVar('files', 'files_dir');
		$filePath = $publicFilesDir . '/' . $submissionFile->getData('path');
		$filePath = preg_replace('/\/\//', '/', $filePath);
		$fileArray = pathinfo($filePath);
		$fileExt  = $fileArray['extension'];

		$excerpt = "";
		if ($fileExt == "docx" || $fileExt == 'doc' || $fileExt == 'xlsx' || $fileExt == 'pptx') {
			$doc = new DocConversion($filePath);
			$text = $doc->convertToText();
			$excerpt = mb_substr($text, 0, EXCERPT_LENGTH);
		}
		else if ($fileExt == "pdf") {
			$parser = new Parser();
			$pdf = $parser->parseFile($filePath);
			$text = $pdf->getText();
			$excerpt = mb_substr($text, 0, EXCERPT_LENGTH);
		}

		$submissionDao = DAORegistry::getDAO('SubmissionDAO');
		$submissionId = $submissionFile->getData('submissionId');
		$submission = $submissionDao->getById($submissionId);

		$duplicateDetectorDAO = DAORegistry::getDAO('DuplicateDetectorDAO');
		$duplicateDetector = $duplicateDetectorDAO->getBySubmissionId($submissionId);

		if (!$duplicateDetector || !$duplicateDetector->getId()) {
			$this->createDuplicateDetector($submission, $submissionFile, $excerpt);
		}
		else {
			$this->updateDuplicateDetector($duplicateDetector, $submission, $submissionFile, $excerpt);
		}

		return $submissionFile;
	}

	function executeForSubmission($hookName, $args) {
		$submission =& $args[0];

		$duplicateDetectorDAO = DAORegistry::getDAO('DuplicateDetectorDAO');
		$duplicateDetector = $duplicateDetectorDAO->getBySubmissionId($submission->getId());

		if (!$duplicateDetector || !$duplicateDetector->getId()) {
			$this->createDuplicateDetector($submission);
		}
		else {
			$this->updateDuplicateDetector($duplicateDetector, $submission);
		}
	}

	private function createDuplicateDetector($submission, $submissionFile = null, $excerpt = null) {
		$duplicateDetectorDAO = DAORegistry::getDAO('DuplicateDetectorDAO');
		$duplicateDetector = $duplicateDetectorDAO->newDataObject();

		$this->populateDuplicateDetector($duplicateDetector, $submission, $submissionFile, $excerpt);

		$duplicateDetectorDAO->insertObject($duplicateDetector);
	}

	private function updateDuplicateDetector($duplicateDetector, $submission, $submissionFile = null, $excerpt = null) {
		$this->populateDuplicateDetector($duplicateDetector, $submission, $submissionFile, $excerpt);

		$duplicateDetectorDAO = DAORegistry::getDAO('DuplicateDetectorDAO');
		$duplicateDetectorDAO->updateObject($duplicateDetector);
	}

	private function populateDuplicateDetector($duplicateDetector, $submission, $submissionFile, $excerpt) {
		$locale = $submission->getLocale();
		$duplicateDetector->setLocale($locale);

		$title = $submission->getTitle($locale) ?? "";
		$duplicateDetector->setTitle($title);

		$abstract = $submission->getAbstract($locale) ?? "";
		$abstract = strip_tags($abstract);
		$duplicateDetector->setAbstract($abstract);

		$author = $submission->getAuthorString() ?? "";
		$duplicateDetector->setAuthor($author);

		$submissionId = $submission->getId();
		$duplicateDetector->setSubmissionId($submissionId);

		if ($excerpt) {
			$duplicateDetector->setExcerpt($excerpt);
		}

		if ($submissionFile) {
			$submissionFileId = $submissionFile->getId();
			$duplicateDetector->setSubmissionFileId($submissionFileId);
		}

		$duplicateDetector->stampDateCreated();
	}

	/**
	 * @copydoc AcronPlugin::parseCronTab()
	 */
	function parseCronTab($hookName, $args) {
		$taskFilesPath =& $args[0];
		$taskFilesPath[] = $this->getPluginPath() . DIRECTORY_SEPARATOR . 'scheduledTasks.xml';

		return false;
	}
}
