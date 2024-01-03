<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('videos_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBiginteger('users_id')->unsigned();
            $table->foreign('users_id')->references('id')->on('users')->onDelete('cascade');
            $table->unsignedBiginteger('videos_id')->unsigned();
            $table->foreign('videos_id')->references('id')->on('videos')->onDelete('cascade');
            $table->integer('progress')->default(0);;
            $table->integer('previous_time')->default(0);;
            $table->integer('total_correct')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videos_users');
    }
};
