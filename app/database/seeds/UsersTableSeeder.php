<?php

class UsersTableSeeder extends Seeder {

	public function run()
	{
		// Uncomment the below to wipe the table clean before populating
		DB::table('users')->truncate();

        User::create(array(
            'name' => 'Admin',
            'username' => 'Admin',
            'email' => 'a.pape@paneon.de',
            'password' => Hash::make('awesome'),
        ));

	}

}
