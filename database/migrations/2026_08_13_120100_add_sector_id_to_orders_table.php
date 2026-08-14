<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sector was previously derived at query time through
     * orders.user_id -> userprofiles.sector_id. That join is needed on every
     * dashboard metric and every order listing, so the sector is stored on the
     * order itself and kept in sync from the customer profile.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('orders', 'sector_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('sector_id')->nullable()->after('user_id');
                $table->index('sector_id');
            });
        }

        // Backfill existing orders from the customer's profile.
        DB::table('orders')
            ->join('userprofiles', 'userprofiles.user_id', '=', 'orders.user_id')
            ->whereNull('orders.sector_id')
            ->whereNotNull('userprofiles.sector_id')
            ->where('userprofiles.sector_id', '>', 0)
            ->update(['orders.sector_id' => DB::raw('userprofiles.sector_id')]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('orders', 'sector_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropIndex(['sector_id']);
                $table->dropColumn('sector_id');
            });
        }
    }
};
