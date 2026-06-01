<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Account;
use App\Models\Report;
use Faker\Factory as Faker;

class ReportSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('id_ID');

        // Ambil data akun yang sudah ada di database dari seeder sebelumnya
        $umkms = Account::where('role', 'umkm')->get();
        $users = Account::where('role', 'user')->get();

        // Antisipasi jika database akun masih kosong
        if ($umkms->isEmpty() || $users->isEmpty()) {
            $this->command->error('Jalankan UmkmSeeder terlebih dahulu agar ada data akun untuk dilaporkan!');
            return;
        }

        // Variasi pesan aduan berdasarkan kategori agar sesuai dengan desain UI kamu
        $laporanSampel = [
            'Ujaran Kebencian' => [
                'Makanan di toko ini basi dan pelayanannya sangat buruk, jangan pernah kesini!',
                'Reviewer sengaja menjatuhkan bisnis orang dengan kata-kata kasar dan fitnah.',
                'Komentar akun ini mengandung unsur penghinaan SARA kepada pemilik toko.'
            ],
            'Penipuan' => [
                'Promosi palsu, harga diskon di aplikasi beda jauh saat ditagih langsung.',
                'Katanya dapet promo beli 1 gratis 1, pas bayar kasirnya bilang sudah habis.',
                'Deskripsi produk bilang barang original, pas datang ternyata barang tiruan kasar.'
            ],
            'Ketidaknyamanan' => [
                'Tempat makannya kotor sekali, banyak kecoa lewat di atas meja pelanggan.',
                'Musik di outlet ini diputar terlalu keras sampai mengganggu ruko sebelahnya.',
                'Antreannya berantakan dan staf tidak ada yang mengatur barisan pembeli.'
            ],
            'Pelecehan' => [
                'Penjualnya tidak sopan sama sekali, marah-marah dan membentak saat ditanya harga.',
                'Staf toko memberikan pesan teks tidak senonoh ke nomor pribadi saya setelah transaksi.',
                'Komentar ini bernada merendahkan pelayan wanita di warung.'
            ]
        ];

        // Buat 20 data laporan tiruan
        for ($i = 0; $i < 20; $i++) {
            // Pilih kategori dan tipe laporan acak
            $kategori = $faker->randomElement(['Ujaran Kebencian', 'Penipuan', 'Ketidaknyamanan', 'Pelecehan']);
            $tipeReport = $faker->randomElement(['umkm', 'comment']);
            
            // Tentukan siapa pelapor (user) dan siapa yang dilaporkan (umkm)
            $pelapor = $users->random();
            $terlapor = $umkms->random();

            // Ambil salah satu teks aduan yang sesuai dengan kategorinya
            $pesanAduan = $faker->randomElement($laporanSampel[$kategori]);

            Report::create([
                'reporter_id'      => $pelapor->_id,
                'reported_user_id' => $terlapor->_id,
                'report_type'      => $tipeReport,
                'report_category'  => $kategori,
                'report_message'   => $pesanAduan,
                // Set status: 80% pending (biar antrean ramai), 20% finished (untuk riwayat)
                'report_status'    => $faker->randomElement(['pending', 'pending', 'pending', 'pending', 'finished']),
                'created_at'       => $faker->dateTimeBetween('-1 months', 'now'),
                'updated_at'       => now(),
            ]);
        }

        $this->command->info('20 Data Laporan Pelanggaran Berhasil Ditambahkan! 🚨');
    }
}