<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Produk extends Model
{
    // Daftarkan kolom yang boleh diisi, tambahkan 'foto' di sini
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