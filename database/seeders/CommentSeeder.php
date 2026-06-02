<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Umkm;
use App\Models\Comment;
use Faker\Factory as Faker;

class CommentSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // 1. Ambil data User (sebagai pemberi komentar) dan UMKM (sebagai penerima)
        $users = Account::where('role', 'user')->get();
        $umkms = Umkm::all();

        // Validasi ketersediaan data dasar
        if ($users->isEmpty() || $umkms->isEmpty()) {
            $this->command->error('Data User atau UMKM kosong! Pastikan sudah menjalankan AccountSeeder dan UmkmSeeder.');
            return;
        }

        // Hapus data komentar lama agar tidak menumpuk saat di-seed ulang
        Comment::truncate(); 

        $jumlahKomentar = 60; // Kita buat 60 komentar acak
        $komentarBerhasil = 0;

        for ($i = 0; $i < $jumlahKomentar; $i++) {
            
            $pelanggan = $users->random();
            $toko = $umkms->random();

            // 2. Generate Rating Acak (1-5)
            // Kita beri bobot lebih banyak ke rating 4 dan 5 agar ulasan UMKM rata-rata bernada positif
            $rating = $faker->randomElement([1, 2, 3, 4, 4, 5, 5, 5, 5]);

            // 3. Sesuaikan isi komentar dengan jumlah bintang
            if ($rating >= 4) {
                $content = $faker->randomElement([
                    'Pelayanan sangat memuaskan, kualitas produk juara!',
                    'Tempatnya nyaman banget, sangat merekomendasikan.',
                    'Harga terjangkau dengan kualitas bintang lima. Top!',
                    'Pasti akan kembali lagi ke sini. Sukses terus!',
                    'Bahan-bahannya fresh, sesuai dengan deskripsi profilnya.'
                ]);
            } elseif ($rating === 3) {
                $content = $faker->randomElement([
                    'Standar saja, sesuai dengan harganya.',
                    'Cukup bagus, tapi masih ada beberapa hal yang bisa ditingkatkan.',
                    'Pelayanan lumayan, meski tadi agak antre sedikit.'
                ]);
            } else {
                $content = $faker->randomElement([
                    'Kurang memuaskan, kualitas agak menurun dibanding dulu.',
                    'Pelayanan sangat lambat, tolong diperbaiki responnya.',
                    'Jujur agak kecewa, tidak sesuai ekspektasi saya.'
                ]);
            }

            // 4. Simpan ke Database
            Comment::create([
                'user_id' => $pelanggan->_id,
                // PENTING: Kita hubungkan ke user_id milik UMKM sesuai relasi controller sebelumnya
                'umkm_id' => $toko->user_id, 
                'content' => $content,
                'rating'  => $rating,       // <--- INJECT RATING DI SINI
                'status'  => 'published',
                'created_at' => $faker->dateTimeBetween('-6 months', 'now'),
                'updated_at' => now(),
            ]);

            $komentarBerhasil++;
        }

        $this->command->info($komentarBerhasil . ' Komentar beserta Rating (Bintang) berhasil di-generate! 🌟');
    }
}