<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ModGiftPackagesTable extends Migration
{
    public function up()
    {
        Schema::table('gift_packages', function (Blueprint $table) {
            $table->string('game_code')->default('');
            $table->bigInteger('condition')->default(0)->comment('Điều khiện để nhận code. Mặc định không có.');
            $table->text('rewards')->change();
            $table->string('server_include')->default('')->comment('Json array of allowed server')->change();
            $table->string('server_exclude')->default('')->comment('Json array of prohibited server')->change();
        });
    }

    public function down()
    {
        Schema::table('gift_packages', function (Blueprint $table) {
            $table->dropColumn('game_code');
            $table->dropColumn('condition');
            $table->string('rewards')->change();
        });
    }
}
