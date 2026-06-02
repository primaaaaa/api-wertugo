<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use App\Models\Gallery; // Ditambahkan
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage; // Ditambahkan

class UmkmController extends Controller
{
    /**
     * POST: /api/umkm/place
     * Mendaftarkan entitas tempat usaha baru (maksimal 1 per akun)
     */
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
}