<?php

namespace Modules\Report\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Report;

class ReportDatabaseSeeder extends Seeder
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
         * Reports Seed
         * ------------------
         */

        // DB::table('reports')->truncate();
        // echo "Truncate: reports \n";

        Report::factory()->count(20)->create();
        $rows = Report::all();
        echo " Insert: reports \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
