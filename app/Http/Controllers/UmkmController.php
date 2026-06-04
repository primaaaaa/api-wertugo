<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use App\Models\Gallery; // Tetap dibiarkan jika digunakan di tempat lain
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        $totalUmkm = Umkm::where('umkm_status', 'active')->count();
        
        $verifiedUmkm = Umkm::where('verification_status', 'verified')
            ->where('verification_status', 'verified')
            ->count();
            
        $suspendedUmkm = Umkm::whereIn('umkm_status', ['suspended', 'banned'])
            ->count();
            
        $umkm = Umkm::with('user')->paginate(10);

        $stats = [
            'total_umkm' => $totalUmkm,
            'verified_umkm' => $verifiedUmkm,
            'suspended_umkm' => $suspendedUmkm ,
        ];

        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data_umkm' => $umkm
        ], 200);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_usaha' => 'required|string|max:255',
            'deskripsi'  => 'required|string',
            'lokasi'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $existingUmkm = Umkm::where('user_id', $request->user()->id)->first();
        if ($existingUmkm) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftar. Akun ini sudah memiliki entitas tempat usaha aktif.'
            ], 403);
        }

        $umkm = Umkm::create([
            'user_id'            => $request->user()->id,
            'nama_usaha'         => $request->nama_usaha,
            'deskripsi'          => $request->deskripsi,
            'lokasi'             => $request->lokasi,
            'media_sosial'       => $request->media_sosial ?? [],
            'is_open'            => false, 
            'jadwal_operasional' => $request->jadwal_operasional ?? [],
            'katalog_galeri'     => [],
            'gambar'             => null // Disiapkan untuk thumbnail
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Tempat usaha berhasil didaftarkan.',
            'data'    => $umkm
        ], 201);
    }

    public function showActive(Request $request)
    {
        $umkm = Umkm::with('user')->where('user_id', $request->user()->id)->first();

        if (!$umkm) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum mendaftarkan tempat usaha.'
            ], 404);
        }

        // 1. HITUNG RATING & ULASAN UNTUK DITAMPILKAN DI DASHBOARD
        $semuaUlasan = \App\Models\Comment::where('umkm_id', $umkm->_id)->get();
        $totalUlasan = $semuaUlasan->count();
        $rataRataRating = $totalUlasan > 0 ? round($semuaUlasan->avg('rating'), 1) : 0;

        // 1.5 HITUNG TOTAL WISHLIST (BERAPA KALI TEMPAT INI DISIMPAN KE BUCKETLIST)
        $totalWishlist = \App\Models\BucketlistPlace::where('place_id', $umkm->_id)->count();

        // 2. FORMAT ULANG JSON (PASTIKAN TIDAK ADA YANG KETINGGALAN LAGI!)
        return response()->json([
            'success' => true,
            'data'    => [
                'id'                  => $umkm->_id ?? $umkm->id,
                'user_id'             => $umkm->user_id,
                'nama_usaha'          => $umkm->nama_usaha,
                'deskripsi'           => $umkm->deskripsi,
                'lokasi'              => $umkm->lokasi,
                'media_sosial'        => $umkm->media_sosial ?? [],
                'is_open'             => $umkm->is_open,
                'jadwal_operasional'  => $umkm->jadwal_operasional ?? [],
                
                // Data Galeri
                'katalog_galeri'      => $umkm->katalog_galeri ?? [], 
                'gambar'              => $umkm->gambar ?? null,       
                
                // Data Pemilik
                'foto_profil'         => $umkm->user ? $umkm->user->foto_profil : null,

                // Data Status (INI BIANG KEROKNYA KEMARIN HILANG!)
                'verification_status' => $umkm->verification_status ?? 'unverified', 
                'umkm_status'         => $umkm->umkm_status ?? 'active',
                
                // Data Statistik
                'rating_avg'          => $rataRataRating,             
                'total_ulasan'        => $totalUlasan,                
                'wishlist_count'      => $totalWishlist
            ]
        ], 200);
    }

    public function updateStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'is_open' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat usaha tidak ditemukan.'], 404);
        }

        $umkm->is_open = $request->is_open;
        $umkm->save();

        return response()->json([
            'success' => true,
            'message' => 'Status operasional berhasil diperbarui.',
            'is_open' => $umkm->is_open
        ], 200);
    }

    public function updateSchedule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'jadwal_operasional' => 'required|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat usaha tidak ditemukan.'], 404);
        }

        $umkm->jadwal_operasional = $request->jadwal_operasional;
        $umkm->save();

        return response()->json([
            'success' => true,
            'message' => 'Jadwal operasional berhasil diperbarui.',
            'data'    => $umkm->jadwal_operasional
        ], 200);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_usaha'   => 'sometimes|string|max:255',
            'deskripsi'    => 'sometimes|string',
            'lokasi'       => 'sometimes|string',
            'media_sosial' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat usaha tidak ditemukan.'], 404);
        }

        $umkm->update($request->only(['nama_usaha', 'deskripsi', 'lokasi', 'media_sosial']));

        return response()->json([
            'success' => true,
            'message' => 'Profil tempat usaha berhasil diperbarui.',
            'data'    => $umkm
        ], 200);
    }

    /**
     * POST: /api/umkm/place/gallery
     * Mengunggah Thumbnail (gambar utama) dan Banner (katalog_galeri array)
     */
    public function uploadGallery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'thumbnail'   => 'nullable|image|mimes:jpeg,png,jpg|max:5126',
            'banners'     => 'nullable|array',
            'banners.*'   => 'image|mimes:jpeg,png,jpg|max:5126' // Validasi setiap isi array banners
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat usaha tidak ditemukan.'], 404);
        }

        $hasUpdate = false;

        // 1. Simpan Thumbnail ke kolom 'gambar'
        if ($request->hasFile('thumbnail')) {
            // Hapus thumbnail lama dari storage jika ada
            if (!empty($umkm->gambar) && Storage::disk('public')->exists($umkm->gambar)) {
                Storage::disk('public')->delete($umkm->gambar);
            }

            $thumbPath = $request->file('thumbnail')->store('galleries/thumbnails', 'public');
            $umkm->gambar = $thumbPath;
            $hasUpdate = true;
        }

        // 2. Simpan banyak Banner ke dalam array 'katalog_galeri'
        if ($request->hasFile('banners')) {
            // Ambil data array katalog lama (jika sudah ada isinya), atau buat array kosong
            $katalogList = $umkm->katalog_galeri ?? [];

            foreach ($request->file('banners') as $banner) {
                $bannerPath = $banner->store('galleries/banners', 'public');
                $katalogList[] = $bannerPath;
            }

            $umkm->katalog_galeri = $katalogList;
            $hasUpdate = true;
        }

        if ($hasUpdate) {
            $umkm->save();
            return response()->json([
                'success' => true,
                'message' => 'Galeri berhasil diperbarui.',
                'data'    => [
                    'gambar' => $umkm->gambar,
                    'katalog_galeri' => $umkm->katalog_galeri
                ]
            ], 200);
        }

        return response()->json(['success' => false, 'message' => 'Tidak ada file yang diunggah.'], 400);
    }

    public function deleteGallery(Request $request, $id)
    {
        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) { 
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $katalogList = $umkm->katalog_galeri ?? [];
        $index = (int) $id;

        if (!isset($katalogList[$index])) {
            return response()->json(['success' => false, 'message' => 'Foto tidak ditemukan.'], 404);
        }

        $imagePath = $katalogList[$index];

        if (Storage::disk('public')->exists($imagePath)) {
            Storage::disk('public')->delete($imagePath);
        }
        
        // Hapus elemen dari array dan reset index
        unset($katalogList[$index]);
        $umkm->katalog_galeri = array_values($katalogList);
        $umkm->save();

        return response()->json([
            'success' => true,
            'message' => 'Foto berhasil dihapus dari galeri.'
        ], 200);
    }

    public function showUmkmDetail($id)
    {
        $umkm = Umkm::with('user')->find($id);

        if (!$umkm) {
            return response()->json(['message' => 'UMKM tidak ditemukan'], 404);
        }

        $semuaUlasan = \App\Models\Comment::where('umkm_id', $umkm->id)->get();
        $totalUlasan = $semuaUlasan->count();
        $rataRataRating = $totalUlasan > 0 ? round($semuaUlasan->avg('rating'), 1) : 0;
        
        $rataRataRating = $totalUlasan > 0 ? round($semuaUlasan->avg('rating'), 1) : 0;

        $ulasanUmkm = \App\Models\Comment::with('user')
            ->where('umkm_id', $umkm->user_id)
            ->latest()
            ->take(10)
            ->get();

        $laporanUmkm = \App\Models\Report::where('reported_user_id', $umkm->user_id)->latest()->get();
        $totalLaporan = $laporanUmkm->count();
        $laporanTerbaru = $laporanUmkm->first();

        return response()->json([
            'message' => 'Detail UMKM berhasil diambil',
            'data' => [
                'profil_umkm' => [
                    'id'                 => $umkm->_id,
                    'nama_usaha'         => $umkm->nama_usaha,
                    'deskripsi'          => $umkm->deskripsi,
                    'lokasi'             => $umkm->lokasi,
                    'media_sosial'       => $umkm->media_sosial,
                    'is_open'            => $umkm->is_open,
                    'jadwal_operasional' => $umkm->jadwal_operasional,
                    'katalog_galeri'     => $umkm->katalog_galeri,
                    'gambar'             => $umkm->gambar, // Menampilkan thumbnail di detail
                    'created_at'         => $umkm->created_at,
                    'rating_avg'         => $rataRataRating, 
                    'total_ulasan'       => $totalUlasan,    
                ],
                'pemilik' => $umkm->user ? [
                    'id'             => $umkm->user->_id,
                    'username'       => $umkm->user->username,
                    'email'          => $umkm->user->email,
                    'country'        => $umkm->user->country,
                    'foto_profil'    => $umkm->user->foto_profil,
                    'account_status' => $umkm->user->account_status ?? 'active',
                ] : null,
                'keamanan' => [
                    'total_laporan'         => $totalLaporan,
                    'pesan_laporan_terbaru' => $laporanTerbaru ? $laporanTerbaru->report_message : null,
                ],
                'ulasan' => $ulasanUmkm
            ]
        ]);
    }   
}