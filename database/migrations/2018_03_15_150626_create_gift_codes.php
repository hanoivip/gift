<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftCodes extends Migration
{

    public function up()
    {
        Schema::create('gift_codes', function (Blueprint $table) {
            $table->increments('id');
            $table->string('code');
            $table->string('pack');
            $table->integer('generate_uid')->nullable()->comment('Định danh người sinh code');
            $table->integer('target_uid')->nullable()->comment('Định danh người được sử dụng code');
            $table->timestamp('use_time')->nullable()->comment('Thời gian thực tế sử dụng code');
            $table->integer('usage_uid')->nullable()->comment('Đinh danh người thực tế đã dùng code');
        });
    }

    public function down()
    {
        Schema::dropIfExists('gift_codes');
    }
}
