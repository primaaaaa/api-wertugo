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
    // Fungsi untuk User menambahkan komentar & rating baru
    public function store(Request $request, $id) 
    {
        // 1. Pastikan User terdeteksi (Tambahkan cek ini)
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized: Token tidak valid atau tidak ditemukan'], 401);
        }

        $request->validate([
            'content' => 'required|string|max:500',
            'rating'  => 'required|numeric|min:1|max:5',
        ]);

        // 2. Simpan dengan aman
        $comment = Comment::create([
            'user_id' => $request->user()->id, 
            'umkm_id' => $id, 
            'content' => $request->input('content'),
            'rating'  => $request->input('rating'),
            'status'  => 'active' 
        ]);

        $comment->load('user');

        return response()->json([
            'message' => 'Komentar dan rating berhasil ditambahkan',
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

    public function reply(Request $request, $id)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $request->validate([
            'reply' => 'required|string|max:1000'
        ]);

        $comment = Comment::find($id);
        if (!$comment) {
            return response()->json(['message' => 'Komentar tidak ditemukan'], 404);
        }

        $comment->reply = $request->input('reply');
        $comment->save();

        return response()->json([
            'success' => true,
            'message' => 'Balasan berhasil dikirim',
            'data' => $comment
        ], 200);
    }
}