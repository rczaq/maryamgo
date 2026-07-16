<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'nama' => 'Admin Maryam Go',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('password123'), // Ini password untuk login nanti
            'status' => 'aktif',
        ]);
    }

    
}