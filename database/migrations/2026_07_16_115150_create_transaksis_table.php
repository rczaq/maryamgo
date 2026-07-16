<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksis', function (Blueprint $table) {
            // Kita pakai string ID agar bisa buat format kustom seperti TRX-9821
            $table->string('id')->primary(); 
            $table->string('nama_pelanggan');
            $table->string('metode_pembayaran'); // misal: Transfer Mandiri, QRIS ShopeePay
            $table->string('atas_nama')->nullable(); // Nama pemilik rekening pembayar
            $table->bigInteger('total_harga');
            $table->string('bukti_pembayaran')->nullable(); // Path lokasi file struk yang di-upload
            
            // Status validasi oleh admin
            $table->enum('status', ['pending', 'disetujui', 'ditolak'])->default('pending');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaksis');
    }
};