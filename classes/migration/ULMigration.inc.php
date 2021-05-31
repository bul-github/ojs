<?php

/**
 * @file classes/migration/ULMigration.inc.php
 *
 * Copyright (c) 2014-2021 Simon Fraser University
 * Copyright (c) 2000-2021 John Willinsky
 * Copyright (c) 2021 Université Laval
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class ULMigration
 * @brief Describe database table structures.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as Capsule;

class ULMigration extends Migration {
	/**
	 * Run the migrations.
	 * @return void
	 */
	public function up() {
		Capsule::schema()->create('file_filters', function (Blueprint $table) {
			$table->bigInteger('file_filter_id')->autoIncrement();
			$table->bigInteger('genre_id');
			$table->smallInteger('mode');
			$table->mediumText('filter_value');
			$table->bigInteger('user_id');
			$table->datetime('date_modified');
		});

		Capsule::schema()->create('duplicate_detectors', function (Blueprint $table) {
			$table->bigInteger('duplicate_detector_id')->autoIncrement();
			$table->bigInteger('submission_id');
			$table->bigInteger('submission_file_id');
			$table->string('locale', 14);
			$table->longText('excerpt');
			$table->mediumText('author');
			$table->mediumText('title');
			$table->mediumText('abstract');
			$table->datetime('date_created');
		});
	}

	/**
	 * Reverse the migration.
	 * @return void
	 */
	public function down() {
		Capsule::schema()->drop('file_filters');
		Capsule::schema()->drop('duplicate_detectors');
	}
}
