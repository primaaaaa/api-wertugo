<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Comment;
use App\Models\Report;
use Faker\Factory as Faker;

class ReportSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil data dari database
        $umkms = Account::where('role', 'umkm')->get();
        $users = Account::where('role', 'user')->get();
        $comments = Comment::all();

        // Validasi ketersediaan data
        if ($umkms->isEmpty() || $users->isEmpty()) {
            $this->command->error('Data User atau UMKM kosong! Jalankan seeder akun terlebih dahulu.');
            return;
        }

        if ($comments->isEmpty()) {
            $this->command->error('Data Komentar kosong! Silakan jalankan CommentSeeder terlebih dahulu.');
            return;
        }

        // Hapus data laporan lama (opsional, agar datanya fresh tidak menumpuk)
        Report::truncate(); 

        $kategoriPelanggaran = ['Ujaran Kebencian', 'Penipuan', 'Ketidaknyamanan', 'Pelecehan'];

        // Buat 20 data laporan
        for ($i = 0; $i < 20; $i++) {
            $kategori = $faker->randomElement($kategoriPelanggaran);
            $tipeReport = $faker->randomElement(['umkm', 'comment']);
            
            $pelapor = $users->random(); // Yang melaporkan selalu user (bisa diubah sesuai kebutuhan)
            
            $terlaporId = null;
            $commentId = null;
            $pesanAduan = '';

            // LOGIKA PINTAR: Jika yang dilaporkan adalah Komentar
            if ($tipeReport === 'comment') {
                $komentarBermasalah = $comments->random();
                
                $terlaporId = $komentarBermasalah->user_id; // Akun yang menulis komentar
                $commentId = $komentarBermasalah->_id;      // TITIPAN ID KOMENTAR
                $pesanAduan = $komentarBermasalah->content; // Jadikan isi komentar sebagai pesan yang dilaporkan
            } 
            // LOGIKA PINTAR: Jika yang dilaporkan adalah UMKM-nya
            else {
                $terlaporId = $umkms->random()->_id;
                $commentId = null; // Tidak ada kaitan dengan komentar
                $pesanAduan = "Toko ini melakukan pelanggaran berat terkait " . strtolower($kategori) . " dan merugikan pelanggan.";
            }

            Report::create([
                'reporter_id'      => $pelapor->_id,
                'reported_user_id' => $terlaporId,
                'comment_id'       => $commentId, // Tersimpan rapi di database
                'report_type'      => $tipeReport,
                'report_category'  => $kategori,
                'report_message'   => $pesanAduan,
                // Perbanyak pending agar antrean UI terlihat ramai
                'report_status'    => $faker->randomElement(['pending', 'pending', 'pending', 'finished']),
                'internal_note'    => null,
                'created_at'       => $faker->dateTimeBetween('-2 months', 'now'),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('20 Data Laporan (dengan Comment ID) Berhasil Digenerate! 🚨');
    }
}