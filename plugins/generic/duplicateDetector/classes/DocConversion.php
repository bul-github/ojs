<?php

/**
 * @file plugins/generic/duplicateDetector/classes/DocConversion.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class DocConversion
 * @ingroup plugins_generic_duplicateDetector
 * @brief Simple class that extracts text from .doc, .docx, xslx and .pptx files.
 *
 */

class DocConversion {

	private $filePath;

	public function __construct($filePath) {
		$this->filePath = $filePath;
	}

	private function read_doc() {
		$fileHandle = fopen($this->filePath, "r");
		$line = @fread($fileHandle, filesize($this->filePath));
		$lines = explode(chr(0x0D), $line);

		$outText = "";
		foreach($lines as $l) {
			$pos = strpos($l, chr(0x00));

			if (($pos !== FALSE) || (strlen($l) == 0)) {
				continue;
			} else {
				$outText .= $l . " ";
			}
		}

		return preg_replace("/[^a-zA-Z0-9\s,.\-\n\r\t@\/_()]/","", $outText);
	}

	private function read_docx() {
		// Create new ZIP archive
		$zip = new ZipArchive();

		$content = '';
		// Open received archive file
		if (true === $zip->open($this->filePath)) {
			// If done, search for the data file in the archive
			if (($index = $zip->locateName("word/document.xml")) !== false) {
				// If found, read it to the string
				$data = $zip->getFromIndex($index);

				// Load XML from a string
				// Skip errors and warnings
				$document = new DOMDocument();
				$document->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

				$text = $document->saveXML();
				// Avoid concatenating 2 words together
				$text = str_replace('<', ' <', $text);
				// Return data without XML formatting tags
				$content = strip_tags($text);
			}

			// Close archive file
			$zip->close();
		}

		// Remove unnecessary spaces
		return preg_replace('!\s+!', ' ', $content);
	}

	function read_xlsx() {
		$zip = new ZipArchive();

		$content = '';
		if (true === $zip->open($this->filePath)) {
			if (($index = $zip->locateName("xl/sharedStrings.xml")) !== false) {
				$data = $zip->getFromIndex($index);

				$document= new DOMDocument();
				$document->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

				$text = $document->saveXML();
				$text = str_replace('<', ' <', $text);
				$content = strip_tags($text);
			}

			$zip->close();
		}

		return preg_replace('!\s+!', ' ', $content);
	}

	function read_pptx() {
		$zip = new ZipArchive();

		$content = '';
		if (true === $zip->open($this->filePath)) {
			// Loop through slide files
			$slide_number = 1;

			while (($index = $zip->locateName("ppt/slides/slide".$slide_number.".xml")) !== false) {
				$data = $zip->getFromIndex($index);

				$document= new DOMDocument();
				$document->loadXML($data, LIBXML_NOENT | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING);

				$text = $document->saveXML();
				$text = str_replace('<', ' <', $text);
				$content .= ' ' . strip_tags($text);

				$slide_number++;
			}

			$zip->close();
		}

		return preg_replace('!\s+!', ' ', $content);
	}

	public function convertToText() {
		if (isset($this->filePath) && !file_exists($this->filePath)) {
			return "File Not exists";
		}

		$fileArray = pathinfo($this->filePath);
		$file_ext = $fileArray['extension'];

		if ($file_ext == "doc") {
			return $this->read_doc();
		} elseif ($file_ext == "docx") {
			return $this->read_docx();
		} elseif ($file_ext == "xlsx") {
			return $this->read_xlsx();
		} elseif ($file_ext == "pptx") {
			return $this->read_pptx();
		}

		return "Invalid File Type";
	}
}
