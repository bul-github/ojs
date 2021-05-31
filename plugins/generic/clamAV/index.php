<?php

/**
 * @defgroup plugins_generic_clamAV ClamAV Plugin
 */

/**
 * @file plugins/generic/clamAV/index.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @ingroup plugins_generic_clamAV
 * @brief Wrapper for ClamAV plugin.
 *
 */
require_once('ClamAVPlugin.inc.php');

return new ClamAVPlugin();

?>
