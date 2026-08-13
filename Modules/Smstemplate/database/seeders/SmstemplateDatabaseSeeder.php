<?php

namespace Modules\Smstemplate\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Smstemplate;

class SmstemplateDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Disable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        /*
         * Smstemplates Seed
         * ------------------
         */

        // DB::table('smstemplates')->truncate();
        // echo "Truncate: smstemplates \n";

        Smstemplate::factory()->count(20)->create();
        $rows = Smstemplate::all();
        echo " Insert: smstemplates \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
