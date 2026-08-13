<?php

namespace Modules\Reminder\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Reminder;

class ReminderDatabaseSeeder extends Seeder
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
         * Reminders Seed
         * ------------------
         */

        // DB::table('reminders')->truncate();
        // echo "Truncate: reminders \n";

        Reminder::factory()->count(20)->create();
        $rows = Reminder::all();
        echo " Insert: reminders \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
