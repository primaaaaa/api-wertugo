<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run()
    {
        // 1. Tentukan kredensial admin
        $adminEmail = 'admin@wertugo.com';
        $adminPassword = 'password123'; // Silakan ubah sesuai keinginanmu

        // 2. Cek apakah admin sudah ada agar tidak terjadi duplikasi saat di-seed ulang
        $existingAdmin = Account::where('email', $adminEmail)->first();

        if (!$existingAdmin) {
            // 3. Cetak akun admin baru
            Account::create([
                'username'    => 'Super Admin',
                'email'       => $adminEmail,
                'password'    => Hash::make($adminPassword),
                'role'        => 'admin',
                'country'     => 'Indonesia',
                'foto_profil' => 'default-profile.png'
            ]);

            $this->command->info('🚨 Akun Admin berhasil diselamatkan!');
            $this->command->info('Email: ' . $adminEmail);
            $this->command->info('Password: ' . $adminPassword);
        } else {
            $this->command->warn('Akun Admin sudah tersedia di database. Tidak ada data baru yang dibuat.');
        }
    }
}