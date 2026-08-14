<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revenue is reported per franchise from payment_history. The sector is
     * stamped on the payment when it is recorded so that historic revenue stays
     * with the franchise that earned it, even if the customer later moves to a
     * different sector.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('payment_history', 'sector_id')) {
            Schema::table('payment_history', function (Blueprint $table) {
                $table->unsignedBigInteger('sector_id')->nullable()->after('order_id');
                $table->index('sector_id');
            });
        }

        // Backfill from the order the payment belongs to.
        //
        // payment_date_time and updated_at are assigned to themselves on
        // purpose: if this database still carries the old
        // ON UPDATE CURRENT_TIMESTAMP on those columns, writing them
        // explicitly stops MySQL from resetting them to the time this
        // migration ran. See the migration that drops that attribute.
        DB::table('payment_history')
            ->join('orders', 'orders.id', '=', 'payment_history.order_id')
            ->whereNull('payment_history.sector_id')
            ->whereNotNull('orders.sector_id')
            ->update([
                'payment_history.sector_id' => DB::raw('orders.sector_id'),
                'payment_history.payment_date_time' => DB::raw('payment_history.payment_date_time'),
                'payment_history.updated_at' => DB::raw('payment_history.updated_at'),
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('payment_history', 'sector_id')) {
            Schema::table('payment_history', function (Blueprint $table) {
                $table->dropIndex(['sector_id']);
                $table->dropColumn('sector_id');
            });
        }
    }
};
