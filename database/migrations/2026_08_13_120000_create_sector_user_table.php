<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maps a Franchise Owner to the Sectors they are allowed to operate in.
     * A franchise can own many sectors, so this is a pivot instead of a
     * single `sector_id` column on the users table.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sector_user')) {
            return;
        }

        Schema::create('sector_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sector_id');

            $table->timestamps();

            $table->unique(['user_id', 'sector_id']);
            $table->index('sector_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('sector_id')->references('id')->on('sectors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sector_user');
    }
};
