<?php

namespace Modules\Duration\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Duration;

class DurationDatabaseSeeder extends Seeder
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
         * Durations Seed
         * ------------------
         */

        // DB::table('durations')->truncate();
        // echo "Truncate: durations \n";

        Duration::factory()->count(20)->create();
        $rows = Duration::all();
        echo " Insert: durations \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
