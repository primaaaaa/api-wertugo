<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function getAllUsers()
    {
        $baseQuery = Account::where('role', 'user');

        $totalActiveUser = (clone $baseQuery)->where('account_status', 'active')->count();
        $totalSuspendedUser = (clone $baseQuery)->whereIn('account_status', ['banned', 'suspended'])->count();

        $users = (clone $baseQuery)->paginate(10);
        return response()->json([
            'stats' => [
                'total_active' => $totalActiveUser,
                'total_suspended' => $totalSuspendedUser
            ],
            'data_user' => $users
        ]);
    }

    public function getAllUmkm()
    {
        // Base query agar tidak menulis ulang kondisi role berulang kali
        $baseQuery = Account::where('role', 'umkm');

        $totalUmkm = (clone $baseQuery)->where('account_status', 'active')->count();

        $verifiedUmkm = (clone $baseQuery)
            ->where('account_status', 'active')
            ->where('verification_status', 'verified')
            ->count();

        $suspendedUmkm = (clone $baseQuery)
            ->whereIn('account_status', ['suspended', 'banned'])
            ->count();

        $umkm = (clone $baseQuery)->paginate(10);

        return response()->json([
            'stats' => [
                'total_umkm' => $totalUmkm,
                'verified_umkm' => $verifiedUmkm,
                'suspended_umkm' => $suspendedUmkm
            ],
            'data_umkm' => $umkm
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:mongodb.account,email',
            'username' => 'required|string|max:255',
            'password' => 'required|min:8',
            'role' => 'required|in:user,umkm,admin',
            'country' => 'required|string',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // validasi file
        ]);

        $fotoPath = 'default-profile.png';
        if ($request->hasFile('foto_profil')) {
            $file = $request->file('foto_profil');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_photos', $filename, 'public');
            $fotoPath = $path; // simpan path relatif: "profile_photos/nama_file.jpg"
        }

        $user = Account::create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'country' => $validated['country'],
            'foto_profil' => $fotoPath,
        ]);

        return response()->json(['message' => 'Berhasil mendaftar', 'data' => $user], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = Account::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // Hapus token lama jika perlu (opsional)
        $user->tokens()->delete();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => (string) $user->_id,
                'name' => $user->username,
                'email' => $user->email,
                'role' => $user->role,
            ]
        ]);
    }

    public function getProfile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'username' => $user->username,
            'foto_profil' => $user->foto_profil ?? 'default-profile.png',
            'email' => $user->email,
            'country' => $user->country
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $rules = [
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:mongodb.account,email,' . $user->_id,
            'password' => 'nullable|min:8|confirmed',
            'foto_profil' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ];

        // ... validasi current_password jika email/password berubah (sama seperti kode Anda)

        $request->validate($rules);

        // Hapus foto lama jika upload foto baru
        if ($request->hasFile('foto_profil')) {
            if ($user->foto_profil && $user->foto_profil !== 'default-profile.png') {
                Storage::disk('public')->delete($user->foto_profil);
            }
            $file = $request->file('foto_profil');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profile_photos', $filename, 'public');
            $user->foto_profil = $path;
        }

        if ($request->has('username'))
            $user->username = $request->username;
        if ($request->has('email') && $request->email != $user->email)
            $user->email = $request->email;
        if ($request->has('password') && !empty($request->password))
            $user->password = Hash::make($request->password);

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => [
                'id' => (string) $user->_id,
                'username' => $user->username,
                'email' => $user->email,
                'foto_profil_url' => $user->foto_profil_url, // gunakan accessor
                'country' => $user->country,
                'role' => $user->role
            ]
        ]);
    }

    public function logout(Request $request)
    {
        // Hapus token yang sedang digunakan untuk request ini
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout berhasil'
        ]);
    }

    public function showUmkmDetail($id)
    {
        // 1. Cari data user berdasarkan ID
        $user = Account::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // 2. Ambil Riwayat Komentar (Maksimal 10 terbaru) beserta data UMKM-nya
        // Pastikan Model Comment sudah punya relasi 'umkm' seperti yang kita bahas sebelumnya
        $riwayatKomentar = \App\Models\Comment::with('umkm')
            ->where('user_id', $id)
            ->latest()
            ->take(10)
            ->get();

        // 3. Ambil Data Laporan (Berapa kali user ini dilaporkan)
        // Kalau dia user biasa, mungkin laporannya masuk sebagai 'comment' (berkomentar kasar)
        $laporanUser = \App\Models\Report::where('reported_user_id', $id)->latest()->get();

        $totalLaporan = $laporanUser->count();
        $laporanTerbaru = $laporanUser->first(); // Mengambil 1 laporan paling baru untuk ditampilkan di kotak merah

        // 4. Susun Semua Data Menjadi Satu Paket JSON
        return response()->json([
            'message' => 'Detail user berhasil diambil',
            'data' => [
                'profil' => [
                    'id' => $user->_id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'foto_profil' => $user->foto_profil ?? 'default-profile.png',
                    'role' => $user->role,
                    'account_status' => $user->account_status ?? 'active',
                    'created_at' => $user->created_at,
                    // 'total_trip' => ... (Opsional: Nanti diisi kalau kamu sudah punya tabel Trip/Plan)
                ],
                'keamanan' => [
                    'total_laporan' => $totalLaporan,
                    'pesan_laporan_terbaru' => $laporanTerbaru ? $laporanTerbaru->report_message : null,
                ],
                'komentar' => $riwayatKomentar
            ]
        ]);
    }

    public function showUserDetail($id)
    {
        // 1. Cari data user berdasarkan ID
        $user = \App\Models\Account::find($id);

        if (!$user) {
            return response()->json(['message' => 'User tidak ditemukan'], 404);
        }

        // 2. Ambil Riwayat Komentar (Maksimal 10 terbaru) beserta data UMKM-nya
        $riwayatKomentar = \App\Models\Comment::with('umkm')
            ->where('user_id', $id)
            ->latest()
            ->take(10)
            ->get();
            
        // Hitung total seluruh komentar yang pernah dibuat user ini (untuk Card Statistik)
        $totalKomentar = \App\Models\Comment::where('user_id', $id)->count();

        // 3. Ambil Data Laporan (Berapa kali user ini dilaporkan)
        $laporanUser = \App\Models\Report::where('reported_user_id', $id)->latest()->get();
        
        $totalLaporan = $laporanUser->count();
        $laporanTerbaru = $laporanUser->first(); 

        // 4. Susun Semua Data Menjadi Satu Paket JSON
        return response()->json([
            'message' => 'Detail user berhasil diambil',
            'data' => [
                'profil' => [
                    'id'             => $user->_id,
                    'username'       => $user->username,
                    'email'          => $user->email,
                    'foto_profil'    => $user->foto_profil ?? 'default-profile.png',
                    'role'           => $user->role,
                    'account_status' => $user->account_status ?? 'active',
                    'created_at'     => $user->created_at,
                ],
                // TAMBAHAN: Untuk mempermudah Frontend mengisi angka di Card atas
                'statistik' => [
                    'total_komentar'   => $totalKomentar,
                    'total_dilaporkan' => $totalLaporan
                ],
                'keamanan' => [
                    'total_laporan'         => $totalLaporan,
                    'pesan_laporan_terbaru' => $laporanTerbaru ? $laporanTerbaru->report_message : null,
                ],
                'komentar' => $riwayatKomentar
            ]
        ]);
    }
}