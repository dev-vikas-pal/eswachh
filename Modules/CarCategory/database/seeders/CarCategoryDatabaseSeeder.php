<?php

namespace Modules\CarCategory\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\CarCategory;

class CarCategoryDatabaseSeeder extends Seeder
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
         * CarCategories Seed
         * ------------------
         */

        // DB::table('carcategories')->truncate();
        // echo "Truncate: carcategories \n";

        CarCategory::factory()->count(20)->create();
        $rows = CarCategory::all();
        echo " Insert: carcategories \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
