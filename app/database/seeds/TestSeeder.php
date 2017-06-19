<?php

class TestSeeder extends Seeder
{

    protected $toTruncate = [
        'clients',
        'users',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Eloquent::unguard();

    }

    protected function cleanup()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        foreach ($this->toTruncate as $table) {
            DB::table($table)->truncate();
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    private function addTestAdmin()
    {

    }

}