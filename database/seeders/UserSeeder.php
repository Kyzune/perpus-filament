<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
          'name' => 'Fikrah Ganteng',
          'email' => 'adminganteng@mail.com',
          'password' => Hash::make('janganlupasolat'),
          'role' => 'super_admin'
        ]);
        User::create([
          'name' => 'Staff Perpustakaan',
          'email' => 'staff@mail.com',
          'password' => Hash::make('janganlupasolat'),
          'role' => 'petugas_perpus'
        ]);
        User::factory()->count(10)->create([
          'role' => 'siswa',
          'balance' => 10000
        ]);
    }
}
