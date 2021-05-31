{**
 * plugins/importexport/users/templates/index.tpl
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2003-2021 John Willinsky
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * List of operations this plugin can perform
 *}
{extends file="layouts/backend.tpl"}

{block name="page"}
	<h1 class="app__pageHeading">
		{$pageTitle}
	</h1>

	<script type="text/javascript">
		// Attach the JS file tab handler.
		$(function() {ldelim}
			$('#importExportTabs').pkpHandler('$.pkp.controllers.TabHandler');
			$('#importExportTabs').tabs('option', 'cache', true);
		{rdelim});
	</script>
	<div id="importExportTabs">
		<ul>
			<li><a href="#import-tab">{translate key="plugins.importexport.users.import.importUsers"}</a></li>
			<li><a href="#export-tab">{translate key="plugins.importexport.users.export.exportUsers"}</a></li>
		</ul>
		<div id="import-tab">
			<script type="text/javascript">
				$(function() {ldelim}
					// Attach the form handler.
					$('#importXmlForm').pkpHandler('$.pkp.controllers.form.FileUploadFormHandler',
						{ldelim}
							$uploader: $('#plupload'),
								uploaderOptions: {ldelim}
									uploadUrl: {plugin_url|json_encode path="uploadImportXML" escape=false},
									baseUrl: {$baseUrl|json_encode}
								{rdelim}
						{rdelim}
					);
				{rdelim});
			</script>
			<form id="importXmlForm" class="pkp_form" action="{plugin_url path="importBounce"}" method="post">
				{csrf}
				{fbvFormArea id="importForm"}
					{* Container for uploaded file *}
					<p>{translate key="plugins.importexport.users.import.instructions"}</p>

					<input type="hidden" name="temporaryFileId" id="temporaryFileId" value="" />
					{fbvFormArea id="file"}
						{fbvFormSection title="common.file"}
							{include file="controllers/fileUploadContainer.tpl" id="plupload"}
						{/fbvFormSection}
					{/fbvFormArea}

					{fbvFormSection title="plugins.importexport.users.importFormat" list=true}
						{fbvElement type="radio" name="importFormat" id="formatOJS3" checked=$institutional|compare:"ojs3" label="plugins.importexport.users.importFormat.ojs3" value="ojs3"}
						{fbvElement type="radio" name="importFormat" id="formatOJS2" checked=$institutional|compare:"ojs2" label="plugins.importexport.users.importFormat.ojs2" value="ojs2"}
						{fbvElement type="radio" name="importFormat" id="formatCSV" checked=$institutional|compare:"csv" label="plugins.importexport.users.importFormat.csv" value="csv"}
					{/fbvFormSection}
					{fbvFormSection title="plugins.importexport.users.confirmationEmail" list=true}
						{fbvElement type="checkbox" name="sendConfirmationEmail" id="sendConfirmationEmail" checked=0 label="plugins.importexport.users.confirmationEmail.send" value=1}
					{/fbvFormSection}

					{fbvFormButtons submitText="plugins.importexport.users.import.importUsers" hideCancel="true"}
				{/fbvFormArea}
			</form>
		</div>
		<div id="export-tab">
			<script type="text/javascript">
				$(function() {ldelim}
					// Attach the form handler.
					$('#exportXmlForm').pkpHandler('$.pkp.controllers.form.FormHandler');
				{rdelim});
			</script>
			<form id="exportXmlForm" class="pkp_form" action="{plugin_url path="export"}" method="post">
				{csrf}
				{fbvFormArea id="exportForm"}
					{capture assign=usersGridUrl}{url router=$smarty.const.ROUTE_COMPONENT component="grid.users.exportableUsers.ExportableUsersGridHandler" pluginName="UserImportExportPlugin" op="fetchGrid" escape=false}{/capture}
					{load_url_in_div id="usersGridContainer" url=$usersGridUrl}
					{fbvFormButtons submitText="plugins.importexport.users.export.exportUsers" hideCancel="true"}
				{/fbvFormArea}
			</form>
		</div>
	</div>
{/block}
