<?php

/**
 * @file plugins/generic/clamAV/ClavAVPlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Copyright (c) 2011 Niall Douglas http://www.nedprod.com/
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_clamAV
 * @brief ClamAV plugin for PKP's OJS.
 *
 */

import('lib.pkp.classes.plugins.GenericPlugin');

use Illuminate\Support\MessageBag;

require_once('Clamd.php');

include('config.inc');

class ClamAVPlugin extends GenericPlugin {
	/**
	 * @copydoc Plugin::register()
	 */
	function register($category, $path, $mainContextId = null) {
		if (parent::register($category, $path, $mainContextId)) {
			if ($this->getEnabled()) {
				HookRegistry::register('FileManager::uploadFile', array(&$this, 'uploadFile'));
				HookRegistry::register('SubmissionFile::validate', array(&$this, 'uploadFile'));
				HookRegistry::register('FileUploadWizardHandler::uploadFile', array(&$this, 'uploadFile'));
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
		return 'ClamAVPlugin';
	}

	/**
	 * Return the localized name of this plugin.
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.generic.clamAV.displayName');
	}

	/**
	 * Return the localized description of this plugin.
	 * @return string
	 */
	function getDescription() {
		return __('plugins.generic.clamAV.description');
	}

	/**
	 * Scans the file to verify if it's safe to upload it.
	 * @param $hookName string
	 * @param $args array
	 * @return boolean false if the file is safe
	 */
	function uploadFile($hookName, $args) {
		$isFileManager = $hookName === "FileManager::uploadFile";
		$isSubmissionFile = $hookName === "SubmissionFile::validate";
		$isFileUploadWizardHandler = $hookName === "FileUploadWizardHandler::uploadFile";

		$fileName = null;
		$errors = null;
		if ($isFileManager || $isFileUploadWizardHandler) {
			$fileName = $args[0];
			$errors =& $args[1];
		}
		else if ($isSubmissionFile) {
			$errors =& $args[0];
			$method = $args[1];

			if ($method === "edit") {
				// The file has already been uploaded, it's not useful to scan it another time.
				return false;
			}
		}

		$isSafe = false;
		$virusScanMsg = "Default message, no operations have been done yet.";

		$clam = new Net_Clamd(CLAMDSOCKET);
		$clam_version = $clam->version();
		if (!$clam_version) {
			$isSafe = false;
			$virusScanMsg = "ClamAV is not running, therefore cannot accept files for virus scanning";
		}
		else {
			$virus = null;
			if (CLAMDISLOCAL) {
				if ($isFileManager || $isFileUploadWizardHandler) {
					$virus = $clam->scan($_FILES[$fileName]['tmp_name']);
				}
				else if ($isSubmissionFile) {
					$virus = $clam->scan($_FILES['file']['tmp_name']);
				}
			}
			else {
				$file = null;
				if ($isFileManager || $isFileUploadWizardHandler) {
					$file = $_FILES[$fileName]['tmp_name'];
				}
				else if ($isSubmissionFile) {
					$file = $_FILES['file']['tmp_name'];
				}

				if ($file) {
					$data = file_get_contents($file);
					$virus = $clam->instream($data);
				}
				else {
					$isSafe = false;
					$virusScanMsg = "An unexpected error has occurred with the file.";
				}
			}

			if ($virus) {
				$isSafe = !('OK' != substr($virus, -2));
				$virus = substr(strstr($virus, ': '), 2);
				$virusScanMsg = 'ClamAV version '.$clam_version.' says: ';
				if ($isSafe) {
					$virusScanMsg = $virusScanMsg.'No virus found';
				}
				else {
					$virusScanMsg = $virusScanMsg.$virus;
				}
			}
		}

		if ($isSafe) {
			// If the file is safe, make sure all other subsequent hooks will be called.
			return false;
		}

		error_log($virusScanMsg);

		if (!$errors) {
			$errors = new MessageBag();
		}
		$errors->add('clamAV', __('common.uploadFailed.virus'));

		// The file is not safe, don't even try to call other hooks, stop the processing now.
		return true;
	}
}

?>
