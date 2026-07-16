<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_detail', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel induk transaksi
            $table->string('transaksis_id');
            $table->foreign('transaksis_id')
                  ->references('id')
                  ->on('transaksis')
                  ->onDelete('cascade');

            // Relasi ke master data produk (sesuaikan nama tabel produkmu, misal: 'produks' atau 'products')
            $table->foreignId('product_id')
                  ->constrained('produks') 
                  ->onDelete('restrict'); // Mencegah produk dihapus jika pernah ada transaksi
            
            $table->integer('jumlah'); // Qty barang yang dibeli
            $table->bigInteger('harga_satuan'); // Mengunci nominal harga saat momen pembelian
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksi_detail');
    }
};