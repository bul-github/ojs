<?php

/**
 * @file plugins/generic/fileFormatFilter/FileFormatFilterPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class FileFormatFilterPlugin
 * @ingroup plugins_generic_fileFormatFilter
 * @see FileFormatFilterPlugin
 *
 * @brief Plugin for filtering the files from a list of included or excluded extensions.
 */

import('lib.pkp.classes.plugins.GenericPlugin');

use Illuminate\Support\MessageBag;

class FileFormatFilterPlugin extends GenericPlugin {
	/**
	 * Register the plugin to properly link the methods.
	 * @param $category
	 * @param $path
	 * @param null $mainContextId
	 * @return bool
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled($mainContextId)) {
				import('plugins.generic.fileFormatFilter.classes.FileFilterDAO');
				$fileFilterDao = new FileFilterDAO();
				DAORegistry::registerDAO('FileFilterDAO', $fileFilterDao);

				// Required to fetch the templates data.
				$this->_registerTemplateResource();

				HookRegistry::register('Templates::Controllers::Grid::Settings::Genre::Form::genreForm', array($this, 'showFileFormatFilterSettings'));
				HookRegistry::register('GenreForm::execute', array(&$this, 'saveFileFormatFilterSettings'));
				//HookRegistry::register('FileManager::uploadFile', array(&$this, 'filterFileByFormat'));
				HookRegistry::register('SubmissionFile::validate', array(&$this, 'filterFileByFormat'));
				HookRegistry::register('FileUploadWizardHandler::uploadFile', array(&$this, 'filterFileByFormat'));
			}
			return true;
		}
		return false;
	}

	/**
	 * Return the name of this plugin.
	 * @return string
	 */
	function getName() {
		return 'FileFormatFilterPlugin';
	}

	/**
	 * @copydoc Plugin::getDisplayName()
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.fileFormatFilter.name');
	}

	/**
	 * @copydoc Plugin::getDescription()
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.fileFormatFilter.description');
	}

	/**
	 * @copydoc Plugin::isSitePlugin()
	 * @return bool
	 */
	function isSitePlugin() {
		return false;
	}

	/**
	 * Add the file format filter parameters to the existing genre form.
	 * @param $hookName
	 * @param $args
	 * @return bool
	 */
	function showFileFormatFilterSettings($hookName, $args) {
		$templateManager = $args[1];
		$output =& $args[2];

		$fileFilterDao = DAORegistry::getDAO('FileFilterDAO');

		$genreId = $templateManager->get_template_vars('genreId');
		$fileFilter = $fileFilterDao->getByGenreId($genreId);

		if ($fileFilter && $fileFilter->getId()) {
			$filterValue = $fileFilter->getFilterValue();
			$filterMode = $fileFilter->getMode();
		}
		else {
			$filterValue = null;
			// The default mode is the exclusion.
			$filterMode = FILTER_MODE_EXCLUDE;
		}

		$templateManager->assign('filterValue', $filterValue);
		$templateManager->assign('filterMode', $filterMode);

		$output .= $templateManager->fetch($this->getTemplateResource('settings.tpl'));

		return false;
	}

	/**
	 * Save file format filter parameters to the database.
	 * @param $hookName
	 * @param $args
	 * @return bool
	 */
	function saveFileFormatFilterSettings($hookName, $args) {
		$formArgs = $args[0];

		$genreId = $formArgs['genreId'];
		$filterMode = $formArgs['filterMode'];
		$formats = $formArgs['formats'];

		$request = $this->getRequest();
		$user = $request->getUser();
		$userId = $user ->getId();

		$date = Core::getCurrentDate();

		$fileFilterDao = DAORegistry::getDAO('FileFilterDAO');
		$fileFilter = $fileFilterDao->getByGenreId($genreId);

		if (!$fileFilter) {
			$fileFilter = new FileFilter();
		}

		$fileFilter->setMode($filterMode);
		$fileFilter->setFilterValue($formats);
		$fileFilter->setUserId($userId);
		$fileFilter->setDateModified($date);

		if ($fileFilter->getId()) {
			$fileFilterDao->updateObject($fileFilter);
		}
		else {
			$fileFilter->setGenreId($genreId);

			$fileFilterDao->insertObject($fileFilter);
		}

		return false;
	}

	/**
	 * Look if the file extension is valid for a particular article genre.
	 * @param $hookName
	 * @param $args
	 * @return bool false if the file is accepted
	 */
	function filterFileByFormat($hookName, $args) {
		//$isFileManager = $hookName === "FileManager::uploadFile";
		$isSubmissionFile = $hookName === "SubmissionFile::validate";
		$isFileUploadWizardHandler = $hookName === "FileUploadWizardHandler::uploadFile";

		$fileName = null;
		$errors = null;
		if ($isFileUploadWizardHandler) {
			$fileIndex = $args[0];
			$errors =& $args[1];
			$genreId = $args[2];

			$fileName = $_FILES[$fileIndex]['name'];
		}
		else if ($isSubmissionFile) {
			$method =& $args[1];

			if ($method === 'add') {
				// The GenreId is not accessible for this step.
				return false;
			}

			$errors =& $args[0];
			$genreId = $args[2]['genreId'];
			$primaryLocale = $args[4];

			$request = Application::get()->getRequest();
			$handler = $request->getRouter()->getHandler();
			$submissionFile = $handler->getAuthorizedContextObject(ASSOC_TYPE_SUBMISSION_FILE);
			$fileName = $submissionFile->_data['name'][$primaryLocale];
		}

		$extension = pathinfo($fileName, PATHINFO_EXTENSION);

		$fileFilterDao = DAORegistry::getDAO('FileFilterDAO');
		$fileFilter = $fileFilterDao->getByGenreId($genreId);

		if (!$fileFilter || !$fileFilter->getId()) {
			return false;
		}

		$message = null;

		if ($extension) {
			$filterValue = $fileFilter->getFilterValue();
			// Remove all spaces (including line breaks).
			$filterValue = preg_replace('/\s+/', '', $filterValue);
			// The extensions list must be separated by the character ','.
			$extensions = explode(',', $filterValue);

			switch ($fileFilter->getMode()) {
				case FILTER_MODE_EXCLUDE:
					if (in_array($extension, $extensions)) {
						// Add a whitespace after the delimiter.
						$formats = preg_replace('/,/', ', ', $filterValue);
						$message = __('plugins.generic.fileFormatFilter.uploadFailed.format.exclusion', array('formats' => $formats));
					}
					break;
				case FILTER_MODE_INCLUDE:
					if (!in_array($extension, $extensions)) {
						// Add a whitespace after the delimiter.
						$formats = preg_replace('/,/', ', ', $filterValue);
						$message = __('plugins.generic.fileFormatFilter.uploadFailed.format.inclusion', array('formats' => $formats));
					}
					break;
				default:
					// Unexpected error, show the default error message at this point.
					$message = __('common.uploadFailed');
			}
		}
		else {
			// Systematically reject all files with no extension.
			$message = __('plugins.generic.fileFormatFilter.uploadFailed.format.empty');
		}

		if ($message) {
			if (!$errors) {
				$errors = new MessageBag();
			}
			$errors->add('fileFormat', $message);
			return true;
		}

		return false;
	}
}
