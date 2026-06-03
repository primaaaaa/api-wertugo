<?php

namespace App\Http\Controllers;

use App\Models\Umkm;
use Illuminate\Http\Request;

class BerandaController extends Controller
{
    /**
     * Mengambil data untuk halaman utama User Biasa
     */
    public function index(Request $request)
    {
        // Ambil data UMKM untuk rekomendasi
        // Syarat: Status harus 'active' (bisa ditambah 'verified' kalau ada field-nya)
        $rekomendasi = Umkm::with('user')
            ->where('umkm_status', 'active') // Acak agar tiap buka beranda rekomendasinya segar
            ->take(10) // Ambil 10 teratas saja agar loading tidak berat
            ->get();

        // Nanti kalau fitur rating sudah jalan, kamu bisa ganti inRandomOrder() 
        // dengan orderBy('rating', 'desc')

        return response()->json([
            'success' => true,
            'message' => 'Data rekomendasi beranda berhasil diambil',
            'data' => [
                'rekomendasi' => $rekomendasi
            ]
        ], 200);
    }

    /**
     * Mengambil detail spesifik satu tempat wisata/UMKM
     */
    public function showDetail($id)
    {
        $umkm = Umkm::with('user')->find($id);

        if (!$umkm) {
            return response()->json(['success' => false, 'message' => 'Tempat tidak ditemukan'], 404);
        }

        // Hitung rating (jika ada ulasan)
        $ulasan = \App\Models\Comment::where('umkm_id', $umkm->user_id)->get();
        $rating = $ulasan->count() > 0 ? round($ulasan->avg('rating'), 1) : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $umkm->_id ?? $umkm->id,
                'nama_usaha' => $umkm->nama_usaha,
                'deskripsi' => $umkm->deskripsi,
                'lokasi' => $umkm->lokasi,
                'is_open' => $umkm->is_open,
                'jadwal_operasional' => $umkm->jadwal_operasional,
                'media_sosial' => $umkm->media_sosial,
                'rating_avg' => $rating,
                'total_ulasan' => $ulasan->count()
            ]
        ], 200);
    }
}