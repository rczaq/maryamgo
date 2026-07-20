<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaksi extends Model
{
    use HasFactory;

    // Nama tabel sesuai dengan file migrasi
    protected $table = 'transaksis';

    // Konfigurasi Primary Key bertipe String (misal: TRX-9821)
    protected $primaryKey = 'id';
    public $incrementing = false;
    protected $keyType = 'string';

    // Mengizinkan semua kolom untuk diisi secara massal
    protected $guarded = [];

    // Tipe data casting agar nominal harga selalu dibaca sebagai integer
    protected $casts = [
        'total_harga' => 'integer',
    ];

    /**
     * Relasi One-to-Many ke Model TransaksiDetail
     * Menggunakan Foreign Key 'transaksis_id' sesuai tabel migrasi 'transaksi_detail'
     */
    public function details()
    {
        return $this->hasMany(TransaksiDetail::class, 'transaksis_id', 'id');
    }
}