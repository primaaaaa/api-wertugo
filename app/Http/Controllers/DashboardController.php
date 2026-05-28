<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(){
        $totalUser = Account::where('role', 'user')->count();
        // $totalUmkm
        // $totalVerifikasi

        return response()->json([
            'total_user' => $totalUser
        ]);


    }
}
