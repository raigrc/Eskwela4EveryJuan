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
        Schema::create('lesson_content', function (Blueprint $table) {
            $table->id('lesson_content_id');
            $table->unsignedBigInteger('lesson_id');
            $table->string('lesson_content_title');
            $table->longText('lesson_content');
            $table->string('picture')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')->references('lesson_id')->on('lessons')->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_content');
    }
};
