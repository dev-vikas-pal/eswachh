<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Customer complaints.
     *
     * sector_id is stamped from the order so a Franchise Owner sees only the
     * complaints raised in their own sectors, and assigned_user_id records the
     * cleaner at the time of the complaint - reassigning the car later must not
     * move an old complaint to somebody else.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('complaints')) {
            return;
        }

        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sector_id')->nullable();
            $table->unsignedBigInteger('assigned_user_id')->nullable();

            $table->text('message');

            // open | closed
            $table->string('status', 20)->default('open');

            // talked | not_talked, set by the cleaner when closing
            $table->string('resolution', 20)->nullable();
            $table->text('resolution_note')->nullable();

            $table->unsignedBigInteger('closed_by')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            $table->integer('deleted_by')->unsigned()->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('order_id');
            $table->index('user_id');
            $table->index('sector_id');
            $table->index('assigned_user_id');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complaints');
    }
};
