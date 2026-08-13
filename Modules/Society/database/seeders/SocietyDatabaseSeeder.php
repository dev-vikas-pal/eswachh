<?php

namespace Modules\Society\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Society;

class SocietyDatabaseSeeder extends Seeder
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
         * Societies Seed
         * ------------------
         */

        // DB::table('societies')->truncate();
        // echo "Truncate: societies \n";

        Society::factory()->count(20)->create();
        $rows = Society::all();
        echo " Insert: societies \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
