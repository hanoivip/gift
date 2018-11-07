<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:web' /*'auth:token'*/])->namespace('Hanoivip\Gift\Controllers')->prefix('user')->group(function () {
    
    // Home
    Route::get('/gift', function () {
        return redirect()->route('gift.use.ui');
    });
    // Người chơi bắt đầu sinh mã code của mình
    Route::get('/gift/generate', 'GiftController@personalGenerateUI')->name('gift.generate.ui');
    // Người chơi thực hiện sinh mã
    Route::post('/gift/generate/result', 'GiftController@generate')->name('gift.generate');
    // Người chơi bắt đầu sử dụng code web only
    Route::get('/gift/use', 'GiftController@useUI')->name('gift.use.ui');
    // Người chơi bắt đầu sử dụng code game
    Route::get('/gift/use2', 'GiftController@use2UI')->name('gift.use2.ui');
    // Người chơi sử dụng code
    Route::post('/gift/use/result', 'GiftController@use')->name('gift.use');
    // Người chơi xem danh sách đã tạo
    Route::get('/gift/history', 'GiftController@history')->name('gift.history');

});

// TODO: find a way to protect
// TODO: find a way to remote invoke
Route::namespace('Hanoivip\Gift\Controllers')->prefix('sys')->group(function () {
    Route::post('/gift/generate', 'GiftController@sysGenerate');
});

// Test: admin integrated
Route::middleware(['web', 'admin'])->namespace('Hanoivip\Gift\Controllers')->prefix('ecmin')->group(function () {
    
    // Home
    Route::get('/gift', function () {
        return redirect()->route('gift.stat');
    });
    // Thống kê tình hình sử dụng code
    Route::get('/gift/stat', 'GiftController@statistics')->name('gift.stat');
    // Sinh mã
    Route::get('/gift/generate', 'GiftController@generateUI')->name('gift.batch-generate.ui');
    Route::post('/gift/generate/result', 'GiftController@batchGenerate')->name('gift.batch-generate');
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