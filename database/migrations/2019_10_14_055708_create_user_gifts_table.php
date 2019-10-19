<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserGiftsTable extends Migration
{
    public function up()
    {
        Schema::create('user_gifts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->string('game_code')->default('');
            $table->string('pack')->comment('Gift package code');
            $table->text('rewards')->comment('Actual rewards for user');
            $table->boolean('used')->default(false);
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('user_gifts');
    }
}
