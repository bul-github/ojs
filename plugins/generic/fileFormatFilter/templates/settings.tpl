{**
 * plugins/generic/fileFormatFilter/templates/settings.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * File Format Filter Settings
 *
 *}
<div id="fileFormatFilterSettings">
	{fbvFormSection title="plugins.generic.fileFormatFilter.settings.title" list=true}
		{fbvElement type="radio" name="filterMode" id="exclude" checked=$filterMode|compare:"0" label="plugins.generic.fileFormatFilter.settings.exclude" value="0"}
		{fbvElement type="radio" name="filterMode" id="include" checked=$filterMode|compare:"1" label="plugins.generic.fileFormatFilter.settings.include" value="1"}
	{/fbvFormSection}
	{capture assign=placeholder}{translate key="plugins.generic.fileFormatFilter.settings.placeholder"}{/capture}
	{fbvElement type="textarea" placeholder=$placeholder label="plugins.generic.fileFormatFilter.settings.hint" name="formats" id="formats" value=$filterValue readonly=false}
</div>
