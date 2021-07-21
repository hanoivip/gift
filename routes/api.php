<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:api')->namespace('Hanoivip\Gift\Controllers')->prefix('api')->group(function () {
    // Sinh mới 1 code
    Route::any('/gift/generate', 'GiftController@generate');
    // Người chơi sử dụng 1 code nhất định
    Route::any('/gift/use', 'GiftController@use');
});

Route::middleware(['auth:api', 'admin'])->namespace('Hanoivip\Gift\Controllers')->prefix('api')->group(function () {
    // Xoá 1 package
    Route::delete('/gift/package', 'PackageController@remove');
    // Thêm mới 1 package
    Route::post('/gift/package', 'PackageController@create');
    // Cập nhật package
    Route::put('/gift/package', 'PackageController@update');
    // Sinh mã theo lô
});