<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Technology', 'slug' => Str::slug('Technology')],
            ['name' => 'Travel', 'slug' => Str::slug('Travel')],
            ['name' => 'Food & Cooking', 'slug' => Str::slug('Food & Cooking')],
            ['name' => 'Lifestyle', 'slug' => Str::slug('Lifestyle')],
            ['name' => 'Science', 'slug' => Str::slug('Science')],
            ['name' => 'Art & Culture', 'slug' => Str::slug('Art & Culture')],
            ['name' => 'Sports', 'slug' => Str::slug('Sports')],
            ['name' => 'Entertainment', 'slug' => Str::slug('Entertainment')],
            ['name' => 'Business & Finance', 'slug' => Str::slug('Business & Finance')],
            ['name' => 'Personal Stories', 'slug' => Str::slug('Personal Stories')]
        ];

        foreach ($categories as &$category) {
            $category['created_at'] = now();
            $category['updated_at'] = now();
        }

        DB::table('categories')->insert($categories);
    }
}
