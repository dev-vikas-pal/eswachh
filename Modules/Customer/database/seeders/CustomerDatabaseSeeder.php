<?php

namespace Modules\Customer\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Tag\Models\Customer;

class CustomerDatabaseSeeder extends Seeder
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
         * Customers Seed
         * ------------------
         */

        // DB::table('customers')->truncate();
        // echo "Truncate: customers \n";

        Customer::factory()->count(20)->create();
        $rows = Customer::all();
        echo " Insert: customers \n\n";

        // Enable foreign key checks!
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
}
