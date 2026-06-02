<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Comment;
use Faker\Factory as Faker;

class CommentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil data user pembeli dan UMKM dari database
        $users = Account::where('role', 'user')->get();
        $umkms = Account::where('role', 'umkm')->get();

        // Pastikan datanya ada sebelum di-looping
        if ($users->isEmpty() || $umkms->isEmpty()) {
            $this->command->error('Data User atau UMKM masih kosong! Pastikan sudah ada data akun.');
            return;
        }

        // Variasi teks review/komentar agar terlihat sangat realistis di UI
        $templateKomentar = [
            'Tempatnya sangat nyaman dan bersih. Makanannya juga enak banget! Rekomen deh pokoknya.',
            'Pelayanannya agak lambat karena lagi ramai, tapi rasa makanannya sepadan dengan waktu tunggunya.',
            'Harga cukup terjangkau untuk kantong mahasiswa. Cocok buat nugas sore-sore.',
            'Barangnya sudah sampai dengan selamat. Packing rapi dan kualitas produk sangat sesuai deskripsi.',
            'Agak kecewa sih, di foto kelihatan besar tapi aslinya porsinya dikit. Tapi rasanya lumayan.',
            'Sumpah ini hidden gem banget! Jarang ada yang tau tapi kualitas pelayanannya bintang lima.',
            'Penjualnya ramah banget dan super fast response. Bakal jadi langganan ini mah.',
            'Fasilitas parkirnya kurang luas, susah kalau bawa mobil. Tapi tempatnya asik buat nongkrong.',
            'Biasa aja sih rasanya, nggak ada yang terlalu spesial. Standar lah sesuai sama harganya.',
            'Kualitas kerajinannya sangat detail dan rapi. Produk lokal yang wajib didukung penuh!'
        ];

        // Kita akan membuat 50 data komentar secara acak
        for ($i = 0; $i < 50; $i++) {
            $pelanggan = $users->random();
            $toko = $umkms->random();

            Comment::create([
                'user_id'    => $pelanggan->_id,
                'umkm_id'    => $toko->_id,
                'content'    => $faker->randomElement($templateKomentar),
                // Bikin 80% komentar aktif, 20% disembunyikan (hidden) karena melanggar/dihapus
                'status'     => $faker->randomElement(['active', 'active', 'active', 'active', 'hidden']), 
                'created_at' => $faker->dateTimeBetween('-4 months', 'now'),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('50 Data Komentar/Review Berhasil Digenerate! 💬');
    }
}