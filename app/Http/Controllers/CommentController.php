<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    // Mengambil semua komentar aktif untuk 1 UMKM tertentu
    public function getUmkmComments($umkm_id)
    {
        // Ambil komentar yang statusnya active, dan bawa data user-nya
        $comments = Comment::with('user')
                           ->where('umkm_id', $umkm_id)
                           ->where('status', 'active') // Jangan tampilkan yang kena 'hidden' dari report!
                           ->latest()
                           ->get();

        return response()->json([
            'message' => 'Berhasil mengambil komentar',
            'data' => $comments
        ]);
    }

    // Fungsi untuk User menambahkan komentar baru
    public function store(Request $request)
    {
        // Validasi input dari Frontend
        $request->validate([
            'user_id' => 'required|string',
            'umkm_id' => 'required|string',
            'content' => 'required|string|max:500',
        ]);

        // Simpan ke MongoDB
        $comment = Comment::create([
            'user_id' => $request->user_id,
            'umkm_id' => $request->umkm_id,
            'content' => $request->input('content'),
            'status'  => 'active' // Default saat pertama kali dibuat
        ]);

        // Opsional: Load data user agar Frontend langsung bisa menampilkan nama dan fotonya
        $comment->load('user');

        return response()->json([
            'message' => 'Komentar berhasil ditambahkan',
            'data' => $comment
        ], 201);
    }

    public function pendingList()
    {
        // Gunakan model Verification milikmu
        // Asumsi relasinya: Verification terhubung ke UMKM, dan UMKM terhubung ke User (Pemilik)
        $pendings = \App\Models\Verification::with(['umkm', 'umkm.user'])
            ->where('verification_status', 'pending')
            ->latest()
            ->paginate(10); // 10 baris per halaman

        return response()->json([
            'success' => true,
            'message' => 'Daftar antrean verifikasi berhasil diambil.',
            'data' => $pendings,
            'total_pending' => $pendings->total()
        ]);
    }
}