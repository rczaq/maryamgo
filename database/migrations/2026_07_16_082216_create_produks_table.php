<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
public function up(): void
{
    Schema::create('produks', function (Blueprint $table) {
        $table->id();
        $table->string('nama_produk');
        $table->string('kode_produk')->unique()->nullable();
        $table->string('slug')->unique(); 
        $table->text('deskripsi')->nullable(); 
        $table->decimal('harga', 12, 2)->default(0); 
        $table->integer('stok')->default(0); 
        $table->enum('status', ['tersedia', 'habis'])->default('tersedia'); 
        $table->string('foto')->nullable(); // <-- KUNCI: Tambahkan kolom foto di sini (nullable karena boleh kosong dulu)
        $table->timestamps(); 
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};