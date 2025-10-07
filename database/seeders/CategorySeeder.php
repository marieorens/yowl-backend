<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Technologies', 'description' => 'Actualités tech, gadgets, innovations'],
            ['name' => 'Gaming', 'description' => 'Jeux vidéo, eSport, gaming news'],
            ['name' => 'Science', 'description' => 'Découvertes scientifiques, espace, recherche'],
            ['name' => 'Développement', 'description' => 'Programmation, développement web, logiciels'],
            ['name' => 'Design', 'description' => 'UI/UX, graphisme, web design'],
            ['name' => 'Cybersécurité', 'description' => 'Sécurité informatique, hacking éthique'],
            ['name' => 'Intelligence Artificielle', 'description' => 'IA, machine learning, deep learning'],
            ['name' => 'Crypto & Blockchain', 'description' => 'Cryptomonnaies, NFTs, blockchain'],
            ['name' => 'Startups', 'description' => 'Entrepreneuriat tech, innovations business'],
            ['name' => 'Mobile', 'description' => 'Apps, développement mobile, actualités'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}