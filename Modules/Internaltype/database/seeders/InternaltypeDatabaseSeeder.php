<?php

namespace Modules\Internaltype\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Internaltype;

class InternaltypeDatabaseSeeder extends Seeder
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
         * Internaltypes Seed
         * ------------------
         */

        // DB::table('internaltypes')->truncate();
        // echo "Truncate: internaltypes \n";

        Internaltype::factory()->count(20)->create();
        $rows = Internaltype::all();
        echo " Insert: internaltypes \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
