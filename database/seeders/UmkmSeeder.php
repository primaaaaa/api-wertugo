<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Umkm;
use Faker\Factory as Faker;

class UmkmSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil semua akun yang memiliki role 'umkm'
        $umkmAccounts = Account::where('role', 'umkm')->get();

        // Antisipasi jika belum ada akun UMKM di database
        if ($umkmAccounts->isEmpty()) {
            $this->command->error('Tidak ada akun dengan role "umkm"! Pastikan sudah membuat akun UMKM terlebih dahulu.');
            return;
        }

        // Hapus data lama agar tidak menumpuk saat di-seed ulang (Opsional)
        Umkm::truncate();

        foreach ($umkmAccounts as $account) {
            Umkm::create([
                'user_id'            => $account->_id,
                // Menggunakan faker company agar namanya terlihat seperti bisnis sungguhan
                'nama_usaha'         => $faker->company, 
                'deskripsi'          => $faker->paragraph(3),
                'lokasi'             => $faker->address,
                // Format array assosiatif untuk media sosial
                'media_sosial'       => [
                    'instagram' => '@' . strtolower(str_replace(' ', '_', $faker->userName)),
                    'whatsapp'  => $faker->phoneNumber
                ],
                // 70% kemungkinan toko sedang buka
                'is_open'            => $faker->boolean(70), 
                // Format array jadwal operasional
                'jadwal_operasional' => [
                    'Senin'  => '08:00 - 20:00',
                    'Selasa' => '08:00 - 20:00',
                    'Rabu'   => '08:00 - 20:00',
                    'Kamis'  => '08:00 - 20:00',
                    'Jumat'  => '08:00 - 22:00',
                    'Sabtu'  => '09:00 - 23:00',
                    'Minggu' => 'Tutup'
                ],
                // Galeri dikosongkan karena nantinya akan diisi lewat Endpoint Upload Gallery
                'katalog_galeri'     => [] 
            ]);
        }

        $this->command->info($umkmAccounts->count() . ' Data UMKM Berhasil Digenerate! 🏪');
    }
}