<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransaksiDetail extends Model
{
    use HasFactory;

    protected $table = 'transaksi_detail'; // Menunjuk ke nama tabel di database kamu

    protected $guarded = [];

    public function transaksi()
    {
        return $this->belongsTo(Transaksi::class, 'transaksis_id', 'id');
    }

    public function product()
    {
        return $this->belongsTo(Produk::class, 'product_id'); 
    }
}