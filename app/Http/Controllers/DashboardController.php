<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Report;
use App\Models\Verification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $totalUser = Account::where('role', 'user')->count();
        $totalUmkm = Account::where('role', 'umkm')->count();
        $totalVerificationPending = Verification::where('verification_status', 'pending')->count();
        $totalReportPending = Report::where('report_status', 'pending')->count();
        // $totalVerifikasi

        return response()->json([
            'total_user' => $totalUser,
            'total_umkm' => $totalUmkm,
            'total_verifikasi' => $totalVerificationPending,
            'total_report' => $totalReportPending
        ]);


    }
}
