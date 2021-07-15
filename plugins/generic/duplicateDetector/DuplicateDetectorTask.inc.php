<?php

/**
 * @file plugins/generic/duplicateDetector/DuplicateDetectorTask.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DuplicateDetectorTask
 * @ingroup plugins_generic_duplicateDetector
 * @brief Class to perform automated duplicate detection as a scheduled task.
 *
 */

use Smalot\PdfParser\Parser;

import('lib.pkp.classes.scheduledTask.ScheduledTask');
import('plugins.generic.duplicateDetector.classes.DuplicateDetectorDAO');

require_once('classes/DocConversion.php');

class DuplicateDetectorTask extends ScheduledTask {

	/**
	 * Constructor.
	 * @param $args array
	 */
	function __construct($args = array()) {
		import('plugins.generic.duplicateDetector.classes.DuplicateDetectorDAO');
		$duplicateDetectorDAO = new DuplicateDetectorDAO();
		DAORegistry::registerDAO('DuplicateDetectorDAO', $duplicateDetectorDAO);
		parent::__construct($args);
	}

	/**
	 * @copydoc ScheduledTask::getName()
	 */
	function getName() {
		return 'DuplicateDetectorTask';
	}

	/**
	 * @copydoc ScheduledTask::executeActions()
	 */
	function executeActions() {
		$numberOfDuplicatesDetector = $this->detect();
		$this->addExecutionLogEntry("$numberOfDuplicatesDetector Duplicate Detector entries Processed.", SCHEDULED_TASK_MESSAGE_TYPE_COMPLETED);
		return true;
	}

	/**
	 * @return int
	 */
	function detect() {
		$numberOfDuplicates = 0;

		$duplicateDetectorDAO = DAORegistry::getDAO('DuplicateDetectorDAO');
		$duplicateDetectors = $duplicateDetectorDAO->getAll()->toArray();

		if (empty($duplicateDetectors)) {
			return $numberOfDuplicates;
		}

		$contextDao = Application::getContextDAO();
		$contexts = $contextDao->getAll()->toArray();

		$submissionDao = DAORegistry::getDAO('SubmissionDAO');

		foreach ($contexts as $context) {
			$submissions = $submissionDao->getByContextId($context->getId())->toArray();

			foreach ($submissions as $submission) {
				foreach ($duplicateDetectors as $duplicateDetector) {
					$submissionId = $duplicateDetector->getSubmissionId();
					$submissionDuplicateDetector = $submissionDao->getById($submissionId);

					if (!$submissionDuplicateDetector) {
						continue;
					}

					if (CHECK_METADATA) {
						$metadataSimilarityRatio = $this->detectInMetadata($duplicateDetector, $submission);

						if ($metadataSimilarityRatio >= DUPLICATE_DETECTION_METADATA_RATIO_THRESHOLD) {
							$this->sendEmailNotice($context, $submissionDuplicateDetector, $submission, __(IN_METADATA), $metadataSimilarityRatio);
							$numberOfDuplicates++;
							continue;
						}
					}

					if (CHECK_FILE) {
						$fileSimilarityRatio = $this->detectInFile($duplicateDetector, $submission);

						if ($fileSimilarityRatio >= DUPLICATE_DETECTION_FILE_RATIO_THRESHOLD) {
							$this->sendEmailNotice($context, $submissionDuplicateDetector, $submission, __(IN_FILE), $fileSimilarityRatio);
							$numberOfDuplicates++;
							continue;
						}
					}
				}
			}
		}

		foreach ($duplicateDetectors as $duplicateDetector) {
			$duplicateDetectorDAO->deleteObject($duplicateDetector);
		}

		return $numberOfDuplicates;
	}

	/**
	 * @param $duplicateDetector
	 * @param $submission
	 * @return int
	 */
	function detectInMetadata($duplicateDetector, $submission) {
		$percentage = 0;

		$duplicateDetectorSubmissionId = $duplicateDetector->getSubmissionId();
		$submissionId = $submission->getId();

		if ($duplicateDetectorSubmissionId != $submissionId) {
			$subMetadata = $this->getMetadata($submission);
			$metadata = $duplicateDetector->getMetadata();

			if (mb_strlen($subMetadata) >= DUPLICATE_METADATA_LENGTH_THRESHOLD && mb_strlen($subMetadata) >= DUPLICATE_METADATA_LENGTH_THRESHOLD) {
				$simChars = similar_text($subMetadata, $metadata, $percentage);

				if ($percentage >= DUPLICATE_DETECTION_METADATA_RATIO_THRESHOLD) {
					$message =  "Submission $duplicateDetectorSubmissionId and $submissionId Metadata similarity: $simChars ($percentage %) $metadata";

					$this->addExecutionLogEntry($message, SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
				}
			}
		}

		return $percentage;
	}

	/**
	 * @param $duplicateDetector
	 * @param $submission
	 * @return int
	 */
	function detectInFile($duplicateDetector, $submission) {
		$percentage = 0;

		$duplicateDetectorSubmissionId = $duplicateDetector->getSubmissionId();
		$submissionId = $submission->getId();

		if ($duplicateDetectorSubmissionId != $submissionId) {
			$submissionFileDao = DAORegistry::getDAO('SubmissionFileDAO');
			$genreDao = DAORegistry::getDAO('GenreDAO');

			$submissionFilesIterator = Services::get('submissionFile')->getMany([
				'submissionIds' => [$submissionId],
			]);

			$publicFilesDir = Config::getVar('files', 'files_dir');

			$submissionFileId = $duplicateDetector->getSubmissionFileId();
			$submissionFileDuplicateDetector = $submissionFileDao->getRevisions($submissionFileId)->first();

			if (!$submissionFileDuplicateDetector) {
				return $percentage;
			}

			$filePathDuplicateDetector = $submissionFileDuplicateDetector->path;
			$filePathDuplicateDetector = preg_replace('/\/\//', '/', $filePathDuplicateDetector);
			$duplicateDetectorExcerpt = $duplicateDetector->getExcerpt();

			foreach ($submissionFilesIterator as $submissionFile) {
				$genreId = $submissionFile->getGenreId();
				$genre = $genreDao->getById($genreId);

				if (!is_null($genre) && $genre->getKey() == 'SUBMISSION') {
					$filePath = $publicFilesDir . '/' . $submissionFile->getData('path');
					$filePath = preg_replace('/\/\//', '/', $filePath);
					$fileArray = pathinfo($filePath);
					$fileExt = $fileArray['extension'];

					$simChars = null;
					$percentage = 0;
					if ($fileExt == "docx" || $fileExt == 'doc' || $fileExt == 'xlsx' || $fileExt == 'pptx') {
						$doc = new DocConversion($filePath);
						$text = $doc->convertToText();
						$excerpt = mb_substr($text, 0, EXCERPT_LENGTH);
						$simChars = similar_text($duplicateDetectorExcerpt, $excerpt, $percentage);
					}
					else if ($fileExt == "pdf") {
						try {
							$parser = new Parser();
							$pdf = $parser->parseFile($filePath);
							$text = $pdf->getText();
							$excerpt = mb_substr($text, 0, EXCERPT_LENGTH);
							$simChars = similar_text($duplicateDetectorExcerpt, $excerpt, $percentage);
						} catch (\Exception $e) {
							//$file = __FILE__;
							//$sourceFile = $e->getFile();
							//$sourceLine = $e->getLine();
							//error_log('PdfParser getText() Exception ' . $sourceFile . ' Line ' . $sourceLine . ' : ' . $e->getMessage() . ' (called from ' . $file . ')');
							return $percentage;
						}
					}

					if ($percentage >= DUPLICATE_DETECTION_FILE_RATIO_THRESHOLD) {
						$message = "Submission $duplicateDetectorSubmissionId and $submissionId File similarity: $simChars ($percentage %) $filePathDuplicateDetector";

						$this->addExecutionLogEntry($message, SCHEDULED_TASK_MESSAGE_TYPE_NOTICE);
					}
				}
			}
		}

		return $percentage;
	}

	/**
	 * @param $submission
	 * @return string
	 */
	function getMetadata($submission) {
		$locale = $submission->getLocale();
		$title = $submission->getTitle($locale);
		if (is_null($title)) {
			$title = "";
		}

		$abstract = $submission->getAbstract($locale);
		if (is_null($abstract)) {
			$abstract = "";
		} else {
			$abstract = strip_tags($abstract);
		}

		$author = $submission->getAuthorString();
		if (is_null($author)) {
			$author = "";
		}

		return $author . " " . $title . " " . $abstract;
	}

	/**
	 * Sends an email to journal principal contact if duplicate is detected
	 * @param $context Context The journal
	 * @param $submission1 Submission in which the duplicate has been detected
	 * @param $submission2 Submission to which the duplicate is similar to
	 * @param $where String Where was the duplicate found?
	 * @param $similarityRatio Float
	 */
	function sendEmailNotice($context, $submission1, $submission2, $where, $similarityRatio) {
		$contactName = $context->getData('contactName');
		$contactEmail = $context->getData('contactEmail');
		$application = PKPApplication::get();
		$request = $application->getRequest();
		$router = $request->getRouter();

		$contextId1 = $submission1->getData('contextId');
		$contextId2 = $submission2->getData('contextId');
		$contextDao = Application::getContextDAO();
		$context1 = $contextDao->getById($contextId1);
		$contextPath1 = $context1->getPath();
		$context2 = $contextDao->getById($contextId2);
		$contextPath2 = $context2->getPath();

		$submissionUrl1 = $router->url($request, $contextPath1, 'workflow', 'access', $submission1->getId());
		$submissionUrl2 = $router->url($request, $contextPath2, 'workflow', 'access', $submission2->getId());

		$contextName1 = $context1->getLocalizedName();
		$contextName2 = $context2->getLocalizedName();

		import('lib.pkp.classes.mail.MailTemplate');
		$mail = new MailTemplate('DUPLICATE_DETECTION_NOTIFICATION', null, $context);
		$mail->setReplyTo(null);
		$mail->addRecipient($contactEmail, $contactName);
		$mail->assignParams(array(
			'contactName' => $contactName,
			'contextName1' => $contextName1,
			'contextName2' => $contextName2,
			'submissionUrl1' => $submissionUrl1,
			'submissionUrl2' => $submissionUrl2,
			'where' => $where,
			'similarityRatio' => $similarityRatio
		));

		$mail->send();
	}
}
