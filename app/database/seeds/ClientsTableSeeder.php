<?php

class ClientsTableSeeder extends Seeder {

	public function run()
	{
		// Clean the table when run
        DB::table('clients')->truncate();

		$clients = array(
            array(
                'name' => 'iRO Demo',
                'host' => 'host1.kon5.net',
                'db_name' => 'iRO_35',
                'serial' => '006D4-PPAD0-R70AA',
                'licenses' => 1,
                'fm_user' => 'fmuser_login',
                'fm_password' => '123',
                'cache_time' => 0,
                'cache_time_detail' => 0,
                'cache_type' => '',
                'api_token' => 'ABC-ABC',
                'last_refresh' => 0,
            ),
        );

        // Run
		DB::table('clients')->insert($clients);
	}

}
