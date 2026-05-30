<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Verification;
use Illuminate\Http\Request;

class VerificationController extends Controller
{
    public function index()
    {
        // 1. Hitung total yang masih pending
        $totalPending = Verification::where('verification_status', 'pending')->count();

        // 2. Ambil maksimal 3 data pending TERBARU untuk Card atas
        $pendingCards = Verification::with('umkm') // Bawa data akun UMKM-nya
                                    ->where('verification_status', 'pending')
                                    ->latest()
                                    ->take(3)
                                    ->get();

        // 3. Ambil riwayat untuk tabel bawah (di-paginate)
        $historyTable = Verification::with('umkm')->latest()->paginate(10);

        return response()->json([
            'stats' => [
                'total_verification_pending' => $totalPending,
            ],
            'pending_cards' => $pendingCards, // Kirim ke FE untuk Card
            'history_table' => $historyTable  // Kirim ke FE untuk Tabel
        ]);
    }

    public function verify($id)
    {
        // Cari ID dari tabel Verifikasi dulu
        $verification = Verification::find($id);

        if (!$verification) {
            return response()->json(['message' => 'Data Verifikasi tidak ditemukan'], 404);
        }

        // Cari akun UMKM yang terhubung
        $umkm = Account::where('_id', $verification->id_umkm)->where('role', 'umkm')->first();

        // UBAH STATUS KEDUANYA AGAR SINKRON
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