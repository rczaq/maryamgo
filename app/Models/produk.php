<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    use HasFactory;

    // WAJIB: Tegaskan nama tabel sesuai dengan file migrasi kamu
    protected $table = 'produks';

    protected $fillable = [
        'nama_produk', 
        'slug', 
        'kode_produk',
        'deskripsi', 
        'harga',
        'stok',  
        'status', 
        'foto' 
    ];
}