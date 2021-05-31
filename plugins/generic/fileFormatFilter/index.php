<?php

/**
 * @defgroup plugins_generic_fileFormatFilter FileFormatFilter Plugin
 */

/**
 * @file plugins/generic/fileFilterFormat/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_fileFormatFilter
 * @brief Wrapper for the file format filter plugin.
 *
 */
require_once('FileFormatFilterPlugin.inc.php');

return new FileFormatFilterPlugin();

?>
