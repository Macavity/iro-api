<?php

class ClientsTableSeeder extends Seeder {

	public function run()
	{
		// Clean the table when run
        DB::table('clients')->truncate();

		$clients = [
            [
                'name' => 'iRO Demo',
                'host' => 'host1.kon5.net',
                'db_name' => 'iRO_35',
                'serial' => '006D4-PPAD0-R70AA',
                'created_at' => time(),
                'updated_at' => time()
            ],
        ];

        // Run
		DB::table('clients')->insert($clients);
	}

}
