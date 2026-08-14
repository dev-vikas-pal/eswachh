<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daily cleaner attendance.
     *
     * A cleaner reports how many of their cars they serviced; anything above
     * zero counts as present. total_cars is stored alongside so the figure can
     * still be read months later, after their round has changed size.
     *
     * One row per cleaner per day, enforced by a unique key rather than by
     * hoping the form is only submitted once.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('cleaner_attendances')) {
            return;
        }

        Schema::create('cleaner_attendances', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sector_id')->nullable();

            $table->date('date');
            $table->unsignedInteger('cars_serviced')->default(0);
            $table->unsignedInteger('total_cars')->default(0);

            // present | absent
            $table->string('status', 20)->default('absent');
            $table->string('note', 255)->nullable();

            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->unsigned()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['user_id', 'date']);
            $table->index('sector_id');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cleaner_attendances');
    }
};
