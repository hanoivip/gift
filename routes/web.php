<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:token'])->namespace('Hanoivip\Gift\Controllers')->prefix('user')->group(function () {
    
    // Người chơi bắt đầu sinh mã code của mình
    Route::get('/gift/generate', 'GiftController@personalGenerateUI')->name('gift.generate.ui');
    
    // Người chơi thực hiện sinh mã
    Route::post('/gift/generate', 'GiftController@generate')->name('gift.generate');
    
    // Người chơi bắt đầu sử dụng code
    Route::get('/gift/use', 'GiftController@useUI')->name('gift.use.ui');
    
    // Người chơi sử dụng code
    Route::post('/gift/use', 'GiftController@use')->name('gift.use');

});

// Test: admin integrated
Route::middleware(['web', 'admin'])->namespace('Hanoivip\Gift\Controllers')->prefix('ecmin')->group(function () {
    
    // Thống kê tình hình sử dụng code
    Route::get('/gift/stat', 'GiftController@statistics')->name('gift.stat');
    
    // Xem danh sách các gói code.
    Route::get('/gift/package', 'PackageController@list')->name('gift.package.list');
    
    // Chuẩn bị thêm mới package
    Route::get('/gift/package/create', 'PackageController@new')->name('gift.package.new');
    
    // Thực hiện thêm package mới
    Route::post('/gift/package/result', 'PackageController@create')->name('gift.package.create');
    
    // Xem chi tiết package
    Route::get('/gift/package/view', 'PackageController@view')->name('gift.package.view');
    
    // Thực hiện chỉnh sửa/cập nhật 
    Route::put('/gift/package/result', 'PackageController@update')->name('gift.package.update');
    
    // Thực hiện xoá
    Route::delete('/gift/package/result', 'PackageController@remove')->name('gift.package.delete');
});