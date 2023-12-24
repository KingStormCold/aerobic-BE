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
        Schema::create('tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_content');
            $table->tinyInteger('serial_answer');
            $table->string('created_by', 100)->default('');
            $table->string('updated_by', 100)->default('');
            $table->unsignedBiginteger('video_id')->unsigned();
            $table->foreign('video_id')->references('id') ->on('videos')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};