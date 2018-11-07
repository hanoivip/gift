<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Thêm 1 số tính chất cho code:
 * + Code hằng số
 * + Code chỉ được áp dụng cho 1 số server
 * 
 * @author hanoivip
 *
 */
class ModGiftPackages extends Migration
{
    public function up()
    {
        Schema::table('gift_packages', function (Blueprint $table) {
            $table->boolean('const_code')->default(false)->comment('All gift code will be the same, equal to package code');
            $table->string('server_include')->comment('Json array of allowed server');
            $table->string('server_exclude')->comment('Json array of prohibited server');
        });
    }

    public function down()
    {
        Schema::table('gift_packages', function (Blueprint $table) {
            $table->dropColumn('const_code');
            $table->dropColumn('server_include');
            $table->dropColumn('server_exclude');
        });
    }
}
