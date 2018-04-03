<?php

use Illuminate\Support\Facades\Route;

Route::middleware('auth:token')->namespace('Hanoivip\Gift\Controllers')->prefix('api')->group(function () {
    
    // Người chơi sinh mã code của mình
    Route::get('/gift/generate', 'GiftController@personalGenerateUI');
    
    // Người chơi sử dụng code
    Route::get('/gift/use', 'GiftController@useUI');
    
    // Sinh mới 1 số code
    Route::post('/gift/generate', 'GiftController@generate');
    
    // Người chơi sử dụng 1 code nhất định
    Route::post('/gift/use', 'GiftController@use');

});

Route::middleware(['auth:token', 'admin'])->namespace('Hanoivip\Gift\Controllers')->prefix('api')->group(function () {
    
    // Xoá 1 package
    Route::delete('/gift/package', 'PackageController@remove');
    
    // Thêm mới 1 package
    Route::post('/gift/package', 'PackageController@create');
    
    // Cập nhật package
    Route::put('/gift/package', 'PackageController@update');
});