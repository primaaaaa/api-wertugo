<?php

namespace App\Http\Controllers;

use App\Models\Comment;
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
        $report = Report::find($id);

        if (!$report) {
            return response()->json(['message' => 'Laporan tidak ditemukan'], 404);
        }

        $aksiKomentar = $request->input('aksi_komentar');
        $statusAkun = $request->input('status_akun');
        $catatanInternal = $request->input('catatan_internal');
        $commentId = $request->input('comment_id'); // Tangkap ID komentarnya

        // 1. Eksekusi Status Akun (Suspend/Aktif)
        if ($statusAkun === 'suspend') {
            $pelanggar = \App\Models\Account::find($report->reported_user_id);
            if ($pelanggar) {
                $pelanggar->account_status = 'suspended';
                $pelanggar->save();
            }
        }

        // 2. Eksekusi Hapus Komentar
        if ($aksiKomentar === 'hapus' && !empty($commentId)) {
            $komentar = Comment::find($commentId);
            if ($komentar) {
                $komentar->status = 'hidden'; // Atau 'deleted' sesuai strategimu
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
}