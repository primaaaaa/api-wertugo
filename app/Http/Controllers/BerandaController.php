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
        $rekomendasi = Umkm::with('user')
            ->where('umkm_status', 'active')
            ->take(10)
            ->get();

        $rekomendasi->map(function ($umkm) {
            $ulasan = \App\Models\Comment::where('umkm_id', $umkm->_id)->get();
            $umkm->rating_avg = $ulasan->count() > 0 ? round($ulasan->avg('rating'), 1) : 0;
            $umkm->total_ulasan = $ulasan->count();
            return $umkm;
        });

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
        $ulasan = \App\Models\Comment::where('umkm_id', $umkm->_id)->get();
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
                'total_ulasan' => $ulasan->count(),
                'user_id' => $umkm->user_id,                 // TAMBAHKAN INI UNTUK FITUR REPORT
                'gambar'             => $umkm->gambar,       // TAMBAHKAN INI
                'katalog_galeri'     => $umkm->katalog_galeri

            ]
        ], 200);
    }
}