<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        // 1. Hitung statistik untuk Dashboard/Header (jika butuh)
        $totalUmkmReport = Report::where('report_type', 'umkm')->count();
        $totalCommentReport = Report::where('report_type', 'comment')->count();
        $totalReportCompleted = Report::where('report_status', 'finished')->count();

        // 2. Ambil data laporan dan BAWA SERTA data Pelapor & Terlapor
        $reportsTable = Report::with(['pelapor', 'terlapor'])
                              ->latest()
                              ->paginate(10);

        // 3. Kembalikan JSON dengan rapi dan variabel yang benar
        return response()->json([
            'stats' => [
                'total_umkm_report' => $totalUmkmReport,
                'total_comment_report' => $totalCommentReport,
                'total_report_completed' => $totalReportCompleted,
            ],
            'data_reports' => $reportsTable // Kirim ke FE untuk Tabel
        ]);
    }

    public function tindakLaporan(Request $request, $id)
    {
        // 1. Cari Laporan berdasarkan ID
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan'], 404);
        }

        // 2. Eksekusi berdasarkan Tipe Laporan
        if ($report->report_type === 'umkm') {
            // Cari akun UMKM-nya
            $umkm = \App\Models\Account::find($report->reported_user_id);
            if ($umkm) {
                $umkm->account_status = 'suspended'; // Ubah status akun
                $umkm->save();
            }
        } 
        elseif ($report->report_type === 'comment') {
            // Cari Komentarnya (Pastikan kamu sudah buat Model Comment)
            // Asumsi: ID komentar tersimpan di reported_user_id
            $comment = \App\Models\Comment::find($report->reported_user_id);
            if ($comment) {
                $comment->status = 'hidden'; // Atau 'suspended' / 'deleted'
                $comment->save();
            }
        }

        // 3. Ubah status laporan menjadi selesai (finished)
        $report->report_status = 'finished';
        $report->save();

        return response()->json([
            'message' => 'Tindakan berhasil! Entitas telah di-suspend dan laporan ditutup.',
            'data' => $report
        ]);
    }
}