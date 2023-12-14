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
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255);
            $table->tinyInteger('level');
            $table->float('price');
            $table->float('promotional_price');
            $table->unsignedBiginteger('subjects_id')->unsigned();
            $table->foreign('subjects_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->string('created_by', 100)->default('');
            $table->string('updated_by', 100)->default('');    
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
