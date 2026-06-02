<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Umkm;
use App\Models\Verification;
use Faker\Factory as Faker;

class VerificationSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil semua data UMKM dari database
        $umkms = Umkm::all();

        if ($umkms->isEmpty()) {
            $this->command->error('Data UMKM masih kosong! Pastikan sudah menjalankan UmkmSeeder.');
            return;
        }

        // Hapus riwayat verifikasi lama agar tidak menumpuk saat di-seed ulang
        Verification::truncate();

        $jumlahData = 0;

        foreach ($umkms as $umkm) {
            // Kita buat skenario: 50% pending, 30% verified, 20% rejected
            $status = $faker->randomElement([
                'pending', 'pending', 'pending', 'pending', 'pending', 
                'verified', 'verified', 'verified', 
                'rejected', 'rejected'
            ]);

            // Cetak pengajuan verifikasi
            Verification::create([
                'id_umkm'             => $umkm->_id,
                // Simulasi nama file dokumen yang diunggah UMKM
                'ktp_file'            => 'dokumen/ktp_' . $umkm->_id . '.jpg',
                'nib_file'            => 'dokumen/nib_' . $umkm->_id . '.pdf',
                'foto_tempat_usaha'   => 'dokumen/toko_' . $umkm->_id . '.jpg',
                'catatan_admin'       => $status === 'rejected' ? 'Dokumen NIB tidak buram dan tidak bisa dibaca.' : null,
                'verification_status' => $status,
                'created_at'          => $faker->dateTimeBetween('-2 months', 'now'),
                'updated_at'          => now(),
            ]);

            // SINKRONISASI: Update status di tabel UMKM agar sama persis
            $umkm->verification_status = $status;
            $umkm->save();

            $jumlahData++;
        }

        $this->command->info($jumlahData . ' Data Pengajuan Verifikasi Berhasil Dibuat! 📑');
    }
}