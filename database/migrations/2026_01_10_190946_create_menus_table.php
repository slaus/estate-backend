<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('layout')->default(1);
            $table->json('name');
            $table->json('properties')->nullable();
            $table->integer('_lft')->default(0);
            $table->integer('_rgt')->default(0);
            $table->integer('parent_id')->nullable();
            $table->boolean('visibility')->default(true);
            $table->timestamps();
            
            $table->index(['_lft', '_rgt', 'parent_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('menus');
    }
};