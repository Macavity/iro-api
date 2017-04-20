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
            $table->string('cache_type')->default('');
            $table->integer('cache_time_detail')->default(0);
            $table->string('last_refresh')->default(0);
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
