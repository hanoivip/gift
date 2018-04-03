<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGiftPackages extends Migration
{

    public function up()
    {
        Schema::create('gift_packages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pack_code')->comment('Mã định danh');
            $table->string('name')->comment('Tên mô tả');
            $table->integer('limit')->default(0)->comment('Số lượng giới hạn');
            $table->string('prefix')->nullable();
            $table->timestamp('start_time')->nullable();
            $table->timestamp('end_time')->nullable();
            $table->string('rewards');
            $table->boolean('allow_users')->default(false)->comment('Cho phép người dùng gọi');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('gift_packages');
    }
}
