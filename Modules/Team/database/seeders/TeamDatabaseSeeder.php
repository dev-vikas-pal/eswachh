<?php

namespace Modules\Team\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Team;

class TeamDatabaseSeeder extends Seeder
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
         * Teams Seed
         * ------------------
         */

        // DB::table('teams')->truncate();
        // echo "Truncate: teams \n";

        Team::factory()->count(20)->create();
        $rows = Team::all();
        echo " Insert: teams \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
