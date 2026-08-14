<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments were only recorded once they completed, so a customer who
     * started a payment and never finished left no trace at all. These columns
     * let a payment be written the moment it is initiated and then completed in
     * place, and record who confirmed a status by hand after checking the bank.
     *
     * The existing 'captured' status is deliberately left alone: revenue
     * reporting keys off it.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('payment_history', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_history', 'razorpay_order_id')) {
                // Links an initiated payment to the callback that completes it.
                $table->string('razorpay_order_id', 100)->nullable()->after('payment_id');
                $table->index('razorpay_order_id');
            }

            if (! Schema::hasColumn('payment_history', 'verified_by')) {
                $table->unsignedBigInteger('verified_by')->nullable()->after('payment_gateway');
            }

            if (! Schema::hasColumn('payment_history', 'verified_at')) {
                $table->timestamp('verified_at')->nullable()->after('verified_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payment_history', function (Blueprint $table) {
            if (Schema::hasColumn('payment_history', 'razorpay_order_id')) {
                $table->dropIndex(['razorpay_order_id']);
                $table->dropColumn('razorpay_order_id');
            }

            foreach (['verified_by', 'verified_at'] as $column) {
                if (Schema::hasColumn('payment_history', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
