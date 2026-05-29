<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;  // ← Pastikan ini ada
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Register, Login, Logout, Get Users
Route::post('/register', [AccountController::class, 'store']);
Route::post('/login', [AccountController::class, 'login']);

// Untuk tahap development, nanti dipindahin lagi ke dalam middleware

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AccountController::class, 'logout']);
    Route::get('/profile', [AccountController::class, 'getProfile']);
    Route::put('/profile/update', [AccountController::class, 'updateProfile']);
    Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::get('/umkm/getumkm', [AccountController::class, 'getAllUmkm']);


    

    // Room
    Route::post('/room/createroom', [RoomController::class, 'createroom']);
    Route::get('/room/getallroom', [RoomController::class, 'getallroom']);
    Route::get('/room/getroom/{id}', [RoomController::class, 'getroom']);

    // Route untuk eksplorasi tempat wisata
    Route::get('/places', [PlaceController::class, 'index']);
    Route::get('/places/search', [PlaceController::class, 'search']);
    Route::get('/places/category/{category}', [PlaceController::class, 'filterByCategory']);
    Route::get('/places/{id}', [PlaceController::class, 'show']);
});