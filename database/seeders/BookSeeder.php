<?php

namespace Database\Seeders;

use App\Models\Book;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = [
          ['title' => 'Jangan Salah Pasangan', 'author' => 'Reza Animasi', 'stock' => 50],
          ['title' => 'Tutorial Potong Rambut dengan kasih sayang', 'author' => 'Reza Animasi', 'stock' => 10],
          ['title' => 'Tutorial Main Fanny', 'author' => 'Reza Kairi', 'stock' => 50],
          ['title' => 'Tutorial Main Thamuz', 'author' => 'Reza Animasi', 'stock' => 50],
        ];

        foreach ($books as $book) {
          Book::create($book);
        }
    }
}
