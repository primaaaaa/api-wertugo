<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Report;
use App\Models\Umkm;
use App\Models\Account;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Hitung statistik untuk Dashboard/Header
        $totalUmkmReport = Report::where('report_type', 'umkm')->count();
        $totalCommentReport = Report::where('report_type', 'comment')->count();
        $totalReportCompleted = Report::where('report_status', 'finished')->count();

        // 2. Ambil data laporan
        // Kita HANYA memanggil relasi 'pelapor' di tingkat database.
        // Untuk 'terlapor', kita akan suntikkan secara manual di bawah karena bisa berupa UMKM atau User biasa.
        $reports = Report::with('pelapor')->latest()->paginate(10);

        // 3. Modifikasi data (Suntik Terlapor & Komentar)
        $reports->getCollection()->transform(function ($report) {
            
            // Jika yang dilaporkan adalah Komentar
            if ($report->report_type === 'comment' && $report->comment_id) {
                $komentar = Comment::find($report->comment_id);
                $penulis = $komentar ? Account::find($komentar->user_id) : null;
                
                $report->terlapor = $penulis;
                // Timpa pesan laporan dengan isi komentar yang asli agar akurat
                $report->report_message = $komentar ? $komentar->content : 'Komentar telah dihapus.';
            } 
            
            // Jika yang dilaporkan adalah UMKM
            elseif ($report->report_type === 'umkm') {
                // reported_user_id di sini adalah ID Pemilik UMKM
                // Kita ambil data UMKM-nya beserta user pemiliknya
                $umkm = Umkm::with('user')->where('user_id', $report->reported_user_id)->first();
                
                // Gunakan data 'user' dari UMKM tersebut sebagai pihak terlapor
                $report->terlapor = $umkm ? $umkm->user : null;
                
                // Tambahkan nama toko ke pesan agar lebih informatif
                if ($umkm && empty($report->report_message)) {
                    $report->report_message = "Melaporkan toko: " . $umkm->nama_usaha;
                }
            }
            
            return $report;
        });

        // 4. Kembalikan JSON
        return response()->json([
            'stats' => [
                'total_umkm_report' => $totalUmkmReport,
                'total_comment_report' => $totalCommentReport,
                'total_report_completed' => $totalReportCompleted,
            ],
            'data_reports' => $reports 
        ]);
    }

    public function tindakLaporan(Request $request, $id)
    {
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan'], 404);
        }

        $aksiKomentar = $request->input('aksi_komentar');
        $statusAkun = $request->input('status_akun');
        $catatanInternal = $request->input('catatan_internal');
        $commentId = $request->input('comment_id'); 

        // 1. Eksekusi Status Akun (Suspend/Aktif)
        if ($statusAkun === 'suspend') {
            $pelanggar = Account::find($report->reported_user_id);
            if ($pelanggar) {
                $pelanggar->account_status = 'suspended';
                $pelanggar->save();
            }
        }

        // 2. Eksekusi Hapus Komentar
        if ($aksiKomentar === 'hapus' && !empty($commentId)) {
            $komentar = Comment::find($commentId);
            if ($komentar) {
                $komentar->status = 'hidden'; 
                $komentar->save();
            }
        }

        // 3. Tutup Laporan
        $report->report_status = 'finished';
        $report->internal_note = $catatanInternal;
        $report->save();

        return response()->json([
            'message' => 'Tindakan berhasil diterapkan secara menyeluruh!',
            'data' => $report
        ]);
    }

    // Fungsi untuk User mengirimkan laporan komentar
    public function store(Request $request)
    {
        // Tambahkan log untuk melihat apa yang sebenarnya sampai ke server
        \Log::info('Data Laporan Masuk:', $request->all());

        $request->validate([
            'reported_user_id' => 'required',
            'comment_id'       => 'required',
            'report_category'  => 'required',
            'report_message'   => 'required'
        ]);

        try {
            $report = new Report();
            $report->reporter_id = $request->user()->id;
            $report->reported_user_id = $request->reported_user_id;
            $report->comment_id = $request->comment_id;
            $report->report_type = 'comment';
            $report->report_category = $request->report_category;
            $report->report_message = $request->report_message;
            $report->report_status = 'pending'; // Pastikan field ini ada di tabel & fillable
            $report->save();

            return response()->json(['success' => true, 'message' => 'Laporan terkirim!'], 201);
        } catch (\Exception $e) {
            \Log::error('Gagal Simpan Laporan: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal simpan ke database: ' . $e->getMessage()], 500);
        }
    }
}