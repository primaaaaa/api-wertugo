<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BerandaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\UmkmController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlaceController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\CommentController;
// Tambahan Controller untuk modul yang baru dimasukkan berdasarkan dokumen
use App\Http\Controllers\BucketlistController;
use App\Http\Controllers\RatingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// ==========================================
// PENGUJIAN USER
// ==========================================
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/admin/dashboard', [DashboardController::class, 'index']);
// Rute pamungkas untuk membaca dokumen verifikasi
Route::get('/view-document', [VerificationController::class, 'serveFile']);
// Route::get('/umkm/getverifylist', [VerificationController::class, 'index']);
// Route::get('/umkm/getumkm', [UmkmController::class, 'index']);

// Route::get('/verifikasi/pending', [VerificationController::class, 'pendingList']);
// ==========================================
// 6.1 MODUL AUTENTIKASI DAN REGISTRASI PENGGUNA
// ==========================================
Route::post('/register', [AccountController::class, 'store']);
Route::post('/login', [AccountController::class, 'login']);

// ==========================================
// RUTE YANG DILINDUNGI (AUTHENTICATED)
// ==========================================
Route::middleware('auth:sanctum')->group(function () {
    
    // ==========================================
    // DATA BERANDA USER 
    // ==========================================
    Route::get('/beranda', [BerandaController::class, 'index']);
    Route::get('/beranda/{id}', [BerandaController::class, 'showDetail']); // TAMBAHKAN INI
    Route::get('/beranda', [BerandaController::class, 'index']);


    Route::post('/reports', [ReportController::class, 'store']);



    // Autentikasi Lanjutan
    Route::post('/logout', [AccountController::class, 'logout']);
    Route::get('/profile', [AccountController::class, 'getProfile']);
    Route::post('/profile/update', [AccountController::class, 'updateProfile']);

    // ==========================================
    // 6.2 MODUL EKSPLORASI TEMPAT WISATA
    // ==========================================
    Route::get('/places', [PlaceController::class, 'index']);
    Route::get('/places/search', [PlaceController::class, 'search']); // Harus di atas {id} agar tidak bentrok
    Route::get('/places/category/{category}', [PlaceController::class, 'filterByCategory']);
    Route::get('/places/{id}', [PlaceController::class, 'show']);

    // ==========================================
    // 6.3 MODUL MANAJEMEN TEMPAT USAHA (KHUSUS PENGUSAHA)
    // ==========================================
    Route::post('/umkm/place', [UmkmController::class, 'store']);
    Route::get('/umkm/place', [UmkmController::class, 'showActive']);
    Route::put('/umkm/place', [UmkmController::class, 'update']);
    Route::put('/umkm/place/status', [UmkmController::class, 'updateStatus']);
    Route::put('/umkm/place/schedule', [UmkmController::class, 'updateSchedule']);
    Route::post('/umkm/place/gallery', [UmkmController::class, 'uploadGallery']);
    Route::put('/umkm/place/gallery', [UmkmController::class, 'uploadGallery']);
    Route::delete('/umkm/place/gallery/{id}', [UmkmController::class, 'deleteGallery']);

    // ==========================================
    // 6.4 MODUL ULASAN DAN RATING PUBLIK
    // ==========================================
    Route::post('/places/{id}/rating', [RatingController::class, 'storeRating']);
    Route::post('/places/{id}/comment', [CommentController::class, 'store']);
    Route::get('/places/{id}/reviews', [RatingController::class, 'getReviews']);
    
    // Endpoint tambahan di luar doc yang kamu miliki
    Route::get('/umkm/{umkm_id}/comments', [CommentController::class, 'getUmkmComments']); 
    Route::post('/comments/{id}/reply', [CommentController::class, 'reply']);

    // ==========================================
    // 6.5 MODUL MANAJEMEN BUCKETLIST (KHUSUS TRAVELLER)
    // ==========================================
    Route::post('/bucketlist', [BucketlistController::class, 'store']);
    Route::get('/bucketlist', [BucketlistController::class, 'index']);
    Route::get('/bucketlist/invitations', [BucketlistController::class, 'getInvitations']); // Di atas {id}
    Route::get('/bucketlist/{id}', [BucketlistController::class, 'show']);
    Route::post('/bucketlist/{id}/places', [BucketlistController::class, 'addPlace']);
    Route::delete('/bucketlist/{id}/places/{place_id}', [BucketlistController::class, 'removePlace']);
    Route::post('/bucketlist/{id}/rating', [BucketlistController::class, 'personalRating']);

    // ==========================================
    // 6.6 MODUL KOLABORASI GRUP BUCKETLIST
    // ==========================================
    Route::post('/bucketlist/{id}/invite', [BucketlistController::class, 'inviteUser']);
    Route::post('/bucketlist/{id}/join', [BucketlistController::class, 'joinSharedList']);
    Route::get('/bucketlist/{id}/members', [BucketlistController::class, 'getMembers']);
    Route::put('/bucketlist/{id}/places/{place_id}', [BucketlistController::class, 'updatePlace']);
    // ==========================================
    // 6.7 MODUL LEGALITAS & VERIFIKASI AKUN BISNIS
    // ==========================================
    Route::post('/umkm/documents', [VerificationController::class, 'uploadDocuments']);
    Route::get('/umkm/verification-status', [VerificationController::class, 'checkStatus']);

    // ==========================================
    // RUTE ADMINISTRATOR & ROOM (Bawaan dari Kodemu)
    // ==========================================
    // Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);
    
    Route::get('/admin/users/{id}', [AccountController::class, 'showUserDetail']);
    Route::get('/reports/getallreport', [ReportController::class, 'index']);
    Route::put('/reports/{id}/tindak', [ReportController::class, 'tindakLaporan']);
    
    // Route::get('/umkm/getumkm', [UmkmController::class, 'index']);
    Route::get('/umkm/getumkm', [UmkmController::class, 'index']);
    Route::get('/user/getusers', [AccountController::class, 'getAllUsers']);

    // Verifikasi UMKM (Admin Side)
    Route::get('/umkm/getverifylist', [VerificationController::class, 'index']);
    Route::put('/umkm/{id}/verify', [VerificationController::class, 'verify']); 
    Route::get('/umkm/{id}', [UmkmController::class, 'showUmkmDetail']);
    Route::get('/verifikasi/pending', [VerificationController::class, 'pendingList']);
    Route::get('/admin/verifikasi/{id}', [VerificationController::class, 'showVerificationDetail']);

    // Room (Chat / Komunitas)
    Route::post('/room/createroom', [RoomController::class, 'createroom']);
    Route::get('/room/getallroom', [RoomController::class, 'getallroom']);
    Route::get('/room/getroom/{id}', [RoomController::class, 'getroom']);
});