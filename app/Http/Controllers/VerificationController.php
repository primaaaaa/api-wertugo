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

    public function pendingList()
    {
        // Gunakan model Verification milikmu
        // Asumsi relasinya: Verification terhubung ke UMKM, dan UMKM terhubung ke User (Pemilik)
        $pendings = Verification::with(['umkm', 'umkm.user'])
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

    public function showVerificationDetail($id)
    {
        // Cari data verifikasi beserta relasi ke UMKM dan User
        $verification = Verification::with(['umkm', 'umkm.user'])->find($id);

        if (!$verification) {
            return response()->json(['message' => 'Data Verifikasi tidak ditemukan'], 404);
        }

        // Susun daftar dokumen (Sesuaikan key 'file' dengan field di database-mu)
        // Kita buat format seragam agar mudah di-looping di Frontend
        $dokumen = [
            [
                'nama' => 'Nomor Induk Berusaha (NIB)',
                'file' => $verification->dokumen_nib ?? null,
                'is_required' => true,
                'tipe_aksi' => 'view' // view (mata) atau download
            ],
            [
                'nama' => 'Nomor Pokok Wajib Pajak (NPWP)',
                'file' => $verification->dokumen_npwp ?? null,
                'is_required' => true,
                'tipe_aksi' => 'view'
            ],
            [
                'nama' => 'Dokumen Legalitas Bangunan',
                'file' => $verification->dokumen_legalitas ?? null,
                'is_required' => true,
                'tipe_aksi' => 'download'
            ],
            [
                'nama' => 'Sertifikat Halal',
                'file' => $verification->dokumen_halal ?? null,
                'is_required' => false,
                'tipe_aksi' => 'view'
            ],
            [
                'nama' => 'Sertifikat Usaha Pariwisata',
                'file' => $verification->dokumen_pariwisata ?? null,
                'is_required' => false,
                'tipe_aksi' => 'view'
            ]
        ];

        // Hitung progress
        $totalDokumen = count($dokumen);
        $dokumenTerunggah = collect($dokumen)->whereNotNull('file')->count();

        return response()->json([
            'success' => true,
            'message' => 'Detail verifikasi berhasil diambil.',
            'data' => [
                'id_verifikasi' => $verification->_id,
                'status' => $verification->verification_status,
                'umkm' => $verification->umkm,
                'pemilik' => $verification->umkm->user ?? null,
                'dokumen' => $dokumen,
                'progress' => [
                    'terunggah' => $dokumenTerunggah,
                    'total' => $totalDokumen,
                    'persentase' => ($dokumenTerunggah / $totalDokumen) * 100
                ]
            ]
        ]);
    }
}