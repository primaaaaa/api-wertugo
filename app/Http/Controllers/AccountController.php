<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PersonalAccessToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AccountController extends Controller
{
    public function getAllUsers()
    {
        $users = Account::paginate(10);
        return response()->json($users);
    }

    public function getAllUmkm(){
        $umkm = Account::where('role', 'umkm')->paginate(10);
        return response()->json($umkm);
    }

    public function store(Request $request)
    {
        // Perbaiki validasi agar sesuai dengan field di collection
        $validated = $request->validate([
            'email' => 'required|string|email|max:255|unique:mongodb.account,email',
            'username' => 'required|string|max:255',
            'password' => 'required|min:8',
            'role' => 'required|in:user,umkm,admin',
            'country' => 'required|string',
        ]);

        $user = Account::create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'country' => $validated['country'],
            'foto_profil' => $validated['foto_profil'] ?? 'default-profile.png'
        ]);

        return response()->json([
            'message' => 'Berhasil mendaftar',
            'data' => $user
        ], 201);
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
            'foto_profil' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:mongodb.account,email,' . $user->_id,
            'password' => 'nullable|min:8|confirmed',
        ];

        $isChangingEmail = $request->has('email') && $request->email != $user->email;
        $isChangingPassword = $request->has('password') && !empty($request->password);

        if ($isChangingEmail || $isChangingPassword) {
            $rules['current_password'] = 'required|string';
        }

        $request->validate($rules);

        if ($isChangingEmail || $isChangingPassword) {
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Password saat ini salah.'
                ], 422);
            }
        }

        if ($request->has('foto_profil')) {
            $user->foto_profil = $request->foto_profil;
        }
        if ($request->has('username')) {
            $user->username = $request->username;
        }
        if ($request->has('email') && $isChangingEmail) {
            $user->email = $request->email;
        }
        if ($request->has('password') && $isChangingPassword) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui',
            'user' => [
                'id' => (string) $user->_id,
                'username' => $user->username,
                'email' => $user->email,
                'foto_profil' => $user->foto_profil ?? 'default-profile.png',
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
}