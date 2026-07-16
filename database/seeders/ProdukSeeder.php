<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Produk;
use Illuminate\Support\Str;

class ProdukSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $produks = [
            [
                'nama_produk' => 'Mouse Gaming Logi X Pro',
                'kode_produk' => 'BRG-001',
                'deskripsi'   => 'Mouse gaming wireless ultra ringan dengan sensor presisi tinggi cocok untuk kompetitif FPS.',
                'harga'       => 1500000,
                'stok'        => 12,
                'foto'        => null, // Sengaja null dulu biar nanti bisa kamu upload manual gambarnya
            ],
            [
                'nama_produk' => 'Keyboard Mechanical Noir Z1',
                'kode_produk' => 'BRG-002',
                'deskripsi'   => 'Keyboard mechanical layout 75% dengan switch premium yang sudah pre-lubed, suara sangat thacky.',
                'harga'       => 850000,
                'stok'        => 25,
                'foto'        => null,
            ],
            [
                'nama_produk' => 'Headset SteelSeries Arctis Nova',
                'kode_produk' => 'BRG-003',
                'deskripsi'   => 'Headset gaming dengan kualitas audio high-fidelity dan fitur noise cancelling mic terbaik.',
                'harga'       => 2300000,
                'stok'        => 0, // Stok 0 untuk mengetes status 'habis' otomatis di tabel
                'foto'        => null,
            ],
            [
                'nama_produk' => 'Monitor Gaming Asus ROG 240Hz',
                'kode_produk' => 'BRG-004',
                'deskripsi'   => 'Monitor panel IPS 24.5 inci refresh rate 240Hz dengan response time 1ms untuk pergerakan visual super mulus.',
                'harga'       => 4200000,
                'stok'        => 5,
                'foto'        => null,
            ],
        ];

        foreach ($produks as $produk) {
            Produk::create([
                'nama_produk' => $produk['nama_produk'],
                'kode_produk' => $produk['kode_produk'],
                'slug'        => Str::slug($produk['nama_produk']),
                'deskripsi'   => $produk['deskripsi'],
                'harga'       => $produk['harga'],
                'stok'        => $produk['stok'],
                'status'      => $produk['stok'] > 0 ? 'tersedia' : 'habis',
                'foto'        => $produk['foto'],
            ]);
        }
    }
}