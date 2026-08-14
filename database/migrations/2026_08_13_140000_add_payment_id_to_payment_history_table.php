<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The gateway's own payment id was stored on the order but not on the
     * payment record, so there was no way to tell whether a given Razorpay
     * payment had already been processed. Without it a resubmitted callback
     * renews a subscription twice and records the money twice.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('payment_history', 'payment_id')) {
            Schema::table('payment_history', function (Blueprint $table) {
                $table->string('payment_id', 100)->nullable()->after('order_id');
                $table->index('payment_id');
            });
        }

        // Deliberately no backfill: orders.payment_id only holds the most
        // recent payment, so copying it onto every historic row for that order
        // would label old payments with a newer payment's id. Existing rows
        // stay null; the column is populated from here on.
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasColumn('payment_history', 'payment_id')) {
            Schema::table('payment_history', function (Blueprint $table) {
                $table->dropIndex(['payment_id']);
                $table->dropColumn('payment_id');
            });
        }
    }
};
