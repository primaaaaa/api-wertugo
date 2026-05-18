<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/register', [AccountController::class, 'store']);
Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);
Route::post('/user/createroom', [RoomController::class, 'createroom']);
Route::get('/user/getallroom', [RoomController::class, 'getallroom']);