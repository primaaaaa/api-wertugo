<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use App\Models\Gallery; // Ditambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // Ditambahkan

class UmkmController extends Controller
{
    public function index(Request $request)
    {
        // 1. Ambil data UMKM dan relasi 'user' untuk menampilkan data pemilik
        // Menggunakan paginate() agar sesuai dengan format yang diharapkan Fronten

        
        $totalUmkm = Umkm::where('umkm_status', 'active')->count();
        
        $verifiedUmkm = Umkm::where('verification_status', 'verified')
            ->where('verification_status', 'verified')
            ->count();
            
        $suspendedUmkm = Umkm::whereIn('umkm_status', ['suspended', 'banned'])
            ->count();
            
        $umkm = Umkm::with('user')->paginate(10);

        // 2. Siapkan data statistik
        $stats = [
            'total_umkm' => $totalUmkm,
            'verified_umkm' => $verifiedUmkm,
            'suspended_umkm' => $suspendedUmkm ,
        ];

        // 3. Kembalikan respons JSON
        return response()->json([
            'success' => true,
            'stats' => $stats,
            'data_umkm' => $umkm
        ], 200);
    }


    public function store(Request $request)
    {
        // 1. Validasi Input
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

        // 2. Cek Aturan Bisnis: Maksimal 1 tempat usaha per akun
        $existingUmkm = Umkm::where('user_id', $request->user()->id)->first();
        if ($existingUmkm) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mendaftar. Akun ini sudah memiliki entitas tempat usaha aktif.'
            ], 403);
        }

        // 3. Simpan ke Database MongoDB
        $umkm = Umkm::create([
            'user_id'            => $request->user()->id,
            'nama_usaha'         => $request->nama_usaha,
            'deskripsi'          => $request->deskripsi,
            'lokasi'             => $request->lokasi,
            'media_sosial'       => $request->media_sosial ?? [],
            'is_open'            => false, // Default tutup saat baru didaftarkan
            'jadwal_operasional' => $request->jadwal_operasional ?? [],
            'katalog_galeri'     => []
        ]);

        // 4. Berikan Respons Sukses
        return response()->json([
            'success' => true,
            'message' => 'Tempat usaha berhasil didaftarkan.',
            'data'    => $umkm
        ], 201);
    }

    /**
     * GET: /api/umkm/place
     * Mengambil data tempat usaha milik akun pengusaha yang sedang login
     */
    public function showActive(Request $request)
    {
        $umkm = Umkm::where('user_id', $request->user()->id)->first();

        if (!$umkm) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum mendaftarkan tempat usaha.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $umkm
        ], 200);
    }

    /**
     * PUT: /api/umkm/place/status
     * Mengubah status operasional (is_open: true/false)
     */
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

    /**
     * PUT: /api/umkm/place/schedule
     * Mengatur atau mengubah jadwal jam buka/tutup operasional
     */
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

    /**
     * PUT: /api/umkm/place
     * Memperbarui informasi detail tempat usaha
     */
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

        // Update hanya kolom yang dikirim di request
        $umkm->update($request->only(['nama_usaha', 'deskripsi', 'lokasi', 'media_sosial']));

        return response()->json([
            'success' => true,
            'message' => 'Profil tempat usaha berhasil diperbarui.',
            'data'    => $umkm
        ], 200);
    }

    /**
     * POST: /api/umkm/place/gallery
     * Mengunggah foto baru ke dalam galeri/katalog
     */
    public function uploadGallery(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat usaha tidak ditemukan.'], 404);
        }

        if ($request->hasFile('image')) {
            // Simpan file ke folder storage/app/public/galleries
            $path = $request->file('image')->store('galleries', 'public');
            
            // Simpan ke database menggunakan Model Gallery yang sudah di-import
            $gallery = Gallery::create([
                'umkm_id'    => $umkm->id, // Gunakan ->id agar otomatis jadi string
                'image_path' => $path
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Foto berhasil diunggah ke katalog.',
                'data'    => $gallery
            ], 201);
        }

        return response()->json(['success' => false, 'message' => 'Gagal mengunggah foto.'], 400);
    }

    /**
     * DELETE: /api/umkm/place/gallery/{id}
     * Menghapus item foto tertentu dari galeri
     */
    public function deleteGallery(Request $request, $id)
    {
        $gallery = Gallery::find($id); // Diubah jadi lebih rapi

        if (!$gallery) {
            return response()->json(['success' => false, 'message' => 'Foto tidak ditemukan.'], 404);
        }

        // Pastikan foto yang dihapus benar-benar milik UMKM si user yang login
        $umkm = Umkm::where('user_id', $request->user()->id)->first();
        if (!$umkm || $gallery->umkm_id !== $umkm->id) { // Diubah menggunakan $umkm->id
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        // Hapus file fisik dari storage (menggunakan Facade Storage)
        Storage::disk('public')->delete($gallery->image_path);
        
        // Hapus data dari database
        $gallery->delete();

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

        // AMBIL SEMUA ulasan untuk menghitung rata-rata rating
        $semuaUlasan = \App\Models\Comment::where('umkm_id', $umkm->user_id)->get();
        $totalUlasan = $semuaUlasan->count();
        
        // Hitung rata-rata rating (dibulatkan 1 angka di belakang koma)
        $rataRataRating = $totalUlasan > 0 ? round($semuaUlasan->avg('rating'), 1) : 0;

        // Ambil 10 ulasan terbaru untuk ditampilkan di tabel/grid
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
                    'created_at'         => $umkm->created_at,
                    'rating_avg'         => $rataRataRating, // DATA BARU
                    'total_ulasan'       => $totalUlasan,    // DATA BARU
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