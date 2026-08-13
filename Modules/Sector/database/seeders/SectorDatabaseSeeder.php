<?php

namespace Modules\Sector\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Sector;

class SectorDatabaseSeeder extends Seeder
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
         * Sectors Seed
         * ------------------
         */

        // DB::table('sectors')->truncate();
        // echo "Truncate: sectors \n";

        Sector::factory()->count(20)->create();
        $rows = Sector::all();
        echo " Insert: sectors \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
