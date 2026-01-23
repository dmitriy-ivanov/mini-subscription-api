<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = ['Daily News', 'Tech Journal', 'World Times'];

        foreach ($products as $productName) {
            Product::firstOrCreate(
                ['name' => $productName],
                ['name' => $productName]
            );
        }
    }
}
