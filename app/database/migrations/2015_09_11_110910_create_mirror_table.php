<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateMirrorTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('job_mirrors', function(Blueprint $table) {
            $table->increments('id');
            $table->timestamps();

            $table->integer('client');
            $table->integer('job_id');
            $table->text('data');

        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('job_mirror');
    }

}
