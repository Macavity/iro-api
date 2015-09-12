<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class AddCacheTypeToClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
        Schema::table('clients', function(Blueprint $table) {
            $table->string('cache_type');
            $table->integer('cache_time_detail');
            $table->string('last_refresh');
        });
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
        Schema::table('clients', function(Blueprint $table) {
            $table->dropColumn("cache_type");
            $table->dropColumn("cache_time_detail");
            $table->dropColumn("last_refresh");
        });
    }

}
