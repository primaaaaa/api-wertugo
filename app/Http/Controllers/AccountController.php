<?php

namespace App\Http\Controllers;

use App\Models\Account;
// use App\Models\User;
use Hash;
use Illuminate\Http\Request;

class AccountController extends Controller
{   

    public function getAllUsers(){
        $users = Account::all();
        return response()->json($users);
    }
    public function store(Request $request){
        $validated = $request->validate([
            'user_email' => 'required|string|max:255|unique:mongodb.users,user_email',
            'user_name' => 'required|string|max:255',
            'user_password' => 'required|min:8',
            'user_role' => 'required|in:user,umkm,admin',
            'user_country' => 'required',
        ]);
        
        $users = Account::create([
            'email' => $validated['email'],
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
            'country' => $validated['country']
        ]);
        return response()->json([
            'message' => 'Berhasil mendaftar',
            'data' => $users
        ]);
    }
}
