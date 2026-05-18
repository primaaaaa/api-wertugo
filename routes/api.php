<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



// Register, Login, Logout, Get Users
Route::post('/register', [AccountController::class, 'store']);
Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);

// Room
Route::post('/room/createroom', [RoomController::class, 'createroom']);
Route::get('/room/getallroom', [RoomController::class, 'getallroom']);
Route::get('/room/getroom/{id}', [RoomController::class, 'getroom'])

