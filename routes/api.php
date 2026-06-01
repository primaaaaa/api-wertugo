<?php
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;  // ← Pastikan ini ada
use App\Http\Controllers\VerificationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Register, Login, Logout, Get Users
Route::post('/register', [AccountController::class, 'store']);
Route::post('/login', [AccountController::class, 'login']);

// Untuk tahap development, nanti dipindahin lagi ke dalam middleware

Route::get('/umkm/getverifylist', [VerificationController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AccountController::class, 'logout']);
    Route::get('/profile', [AccountController::class, 'getProfile']);
    Route::put('/profile/update', [AccountController::class, 'updateProfile']);
    Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);
    Route::get('/admin/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports/getallreport', [ReportController::class, 'index']);


    // Report Notice
    Route::put('/reports/{id}/tindak', [ReportController::class, 'tindakLaporan']);

    // Komentar
    Route::get('/umkm/{umkm_id}/comments', [CommentController::class, 'getUmkmComments']);
    Route::post('/comments', [CommentController::class, 'store']);

    //verifikasi Umkm
    // Route::get
    Route::get('/umkm/getumkm', [AccountController::class, 'getAllUmkm']);
    
    Route::put('/umkm/{id}/verify', [VerificationController::class, 'verify']); 

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