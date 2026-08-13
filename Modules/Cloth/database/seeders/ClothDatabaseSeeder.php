<?php

namespace Modules\Cloth\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Cloth;

class ClothDatabaseSeeder extends Seeder
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
         * Cloths Seed
         * ------------------
         */

        // DB::table('cloths')->truncate();
        // echo "Truncate: cloths \n";

        Cloth::factory()->count(20)->create();
        $rows = Cloth::all();
        echo " Insert: cloths \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
