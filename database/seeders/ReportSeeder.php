<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Umkm;
use App\Models\Comment;
use App\Models\Report;
use Faker\Factory as Faker;

class ReportSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil data pendukung dari database
        $users = Account::where('role', 'user')->get();
        $umkms = Umkm::all();
        $comments = Comment::all();

        // Validasi ketersediaan data dasar
        if ($users->isEmpty() || $umkms->isEmpty()) {
            $this->command->error('Data User atau UMKM kosong! Pastikan sudah menjalankan AccountSeeder dan UmkmSeeder.');
            return;
        }

        // Hapus data laporan lama agar tidak menumpuk
        Report::truncate(); 

        $kategoriPelanggaran = ['Ujaran Kebencian', 'Penipuan', 'Ketidaknyamanan', 'Pelecehan', 'Spam'];
        $jumlahData = 0;

        // Kita buat 20 data laporan
        for ($i = 0; $i < 20; $i++) {
            
            // Tentukan tipe laporan: jika tidak ada data komentar di DB, paksa jadi laporan UMKM
            $tipeReport = ($comments->isNotEmpty() && $faker->boolean(50)) ? 'comment' : 'umkm';
            
            $pelapor = $users->random(); // Laporan selalu datang dari User biasa
            
            $terlaporId = null;
            $commentId = null;
            $pesanAduan = '';
            $kategori = $faker->randomElement($kategoriPelanggaran);

            // LOGIKA 1: Laporan terhadap sebuah Komentar
            if ($tipeReport === 'comment') {
                $komentarBermasalah = $comments->random();
                
                $terlaporId = $komentarBermasalah->user_id; // ID Pembuat komentar
                $commentId = $komentarBermasalah->_id;      // Titipkan ID komentarnya
                $pesanAduan = "Komentar ini sangat tidak pantas: '" . $komentarBermasalah->content . "'";
            } 
            
            // LOGIKA 2: Laporan terhadap sebuah Toko/UMKM
            else {
                $umkmBermasalah = $umkms->random();
                
                // PENTING: Kita bidik user_id (ID Pemilik UMKM) agar sinkron dengan relasi di Controller-mu
                $terlaporId = $umkmBermasalah->user_id; 
                $commentId = null; 
                $pesanAduan = "Toko " . $umkmBermasalah->nama_usaha . " melakukan pelanggaran terkait " . strtolower($kategori) . " kepada pelanggan.";
            }

            // Simpan ke MongoDB
            Report::create([
                'reporter_id'      => $pelapor->_id,
                'reported_user_id' => $terlaporId,
                'comment_id'       => $commentId,
                'report_type'      => $tipeReport,
                'report_category'  => $kategori,
                'report_message'   => $pesanAduan,
                // Rasio: Lebih banyak 'pending' agar UI Admin terlihat ada antrean
                'report_status'    => $faker->randomElement(['pending', 'pending', 'pending', 'finished']),
                'internal_note'    => null,
                'created_at'       => $faker->dateTimeBetween('-1 months', 'now'),
                'updated_at'       => now(),
            ]);

            $jumlahData++;
        }

        $this->command->info($jumlahData . ' Data Laporan (Report) Berhasil Digenerate! 🚨');
    }
}