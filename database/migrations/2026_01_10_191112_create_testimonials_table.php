<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['text', 'image', 'video'])->default('text');
            $table->json('description')->nullable();
            $table->text('text')->nullable();
            $table->text('image')->nullable();
            $table->longText('video')->nullable();
            $table->smallInteger('order')->default(0);
            $table->boolean('visibility')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('testimonials');
    }
};