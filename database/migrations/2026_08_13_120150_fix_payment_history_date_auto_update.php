<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * payment_history.payment_date_time was declared with
     * ON UPDATE CURRENT_TIMESTAMP, so any write to a payment row silently
     * rewrote the date the payment was taken. That is wrong for a business
     * timestamp and it breaks revenue reporting by date, which now depends on
     * this column. The date is always written explicitly on insert, so the
     * automatic behaviour is not needed.
     *
     * @return void
     */
    public function up()
    {
        if (! $this->isMysql()) {
            return;
        }

        DB::statement('ALTER TABLE `payment_history` MODIFY `payment_date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! $this->isMysql()) {
            return;
        }

        DB::statement('ALTER TABLE `payment_history` MODIFY `payment_date_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    }

    private function isMysql(): bool
    {
        return Schema::getConnection()->getDriverName() === 'mysql';
    }
};
