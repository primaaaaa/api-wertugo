<?php

namespace App\Http\Controllers;

use App\Models\Verification;
use App\Models\Umkm; // UBAH: Menggunakan model Umkm, bukan Account
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        // 1. Hitung total yang masih pending
        $totalPending = Verification::where('verification_status', 'pending')->count();

        // 2. Ambil maksimal 3 data pending TERBARU untuk Card atas
        $pendingCards = Verification::with('umkm.user') // Pastikan relasi di model Verification mengarah ke Umkm::class
                                    ->where('verification_status', 'pending')
                                    ->latest()
                                    ->take(3)
                                    ->get();

        // 3. Ambil riwayat untuk tabel bawah (di-paginate)
        $historyTable = Verification::with('umkm.user')->latest()->paginate(10);

        return response()->json([
            'stats' => [
                'total_verification_pending' => $totalPending,
            ],
            'pending_cards' => $pendingCards, 
            'history_table' => $historyTable  
        ]);
    }

    public function verify($id)
    {
        // 1. Cari dokumen dari tabel/collection Verifikasi
        $verification = Verification::find($id);

        if (!$verification) {
            return response()->json(['message' => 'Data Verifikasi tidak ditemukan'], 404);
        }

        // 2. Cari UMKM yang terhubung menggunakan model Umkm
        // Asumsi: kolom di tabel verifikasi bernama 'id_umkm' atau 'umkm_id'
        $umkm = Umkm::find($verification->id_umkm);

        // 3. UBAH STATUS KEDUANYA AGAR SINKRON
        $verification->verification_status = 'verified';
        $verification->save();

        if ($umkm) {
            $umkm->verification_status = 'verified';
            $umkm->save();
        }

        return response()->json([
            'message' => 'UMKM dan berkas berhasil diverifikasi!',
            'data' => $verification
        ]);
    }
}