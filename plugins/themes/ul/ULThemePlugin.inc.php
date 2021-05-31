<?php

/**
 * @file plugins/themes/ul/ULThemePlugin.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ULThemePlugin
 * @ingroup plugins_themes_ul
 *
 * @brief Université Laval theme
 */

import('lib.pkp.classes.plugins.ThemePlugin');

class ULThemePlugin extends ThemePlugin {

	/**
	 * Initialize the theme's styles, scripts and hooks. This is run on the
	 * currently active theme and it's parent themes.
	 *
	 * @return null
	 */
	public function init() {
		//parent::init();
		$this->setParent('defaultthemeplugin');

		// Themes for the administration dashboard.
		$this->addStyle('ul-style-revisions', 'styles/revisions.less', array('contexts' => 'backend'));
		$this->addStyle('ul-style-modal', 'styles/modal.less', array('contexts' => 'backend'));
		$this->addStyle('ul-style', 'styles/ul.less', array('contexts' => array('frontend', 'backend')));
	}

	/**
	 * Get the display name of this plugin
	 * @return string
	 */
	function getDisplayName() {
		return __('plugins.themes.ul.name');
	}

	/**
	 * Get the description of this plugin
	 * @return string
	 */
	function getDescription() {
		return __('plugins.themes.ul.description');
	}
}

?>
