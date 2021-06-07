<?php

/**
 * @defgroup plugins_generic_duplicateDetector DuplicateDetector Plugin
 */

/**
 * @file plugins/generic/duplicateDetector/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_duplicateDetector
 * @brief Wrapper for DuplicateDetector plugin.
 *
 */
require_once('DuplicateDetectorPlugin.inc.php');

return new DuplicateDetectorPlugin();

?>
