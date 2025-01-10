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
            ['name' => 'Comedy', 'slug' => Str::slug('Comedy')],
            ['name' => 'Romance', 'slug' => Str::slug('Romance')],
            ['name' => 'Horror', 'slug' => Str::slug('Horror')],
            ['name' => 'Adventure', 'slug' => Str::slug('Adventure')],
            ['name' => 'Fiction', 'slug' => Str::slug('Fiction')],
            ['name' => 'Fantasy', 'slug' => Str::slug('Fantasy')],
            ['name' => 'Drama', 'slug' => Str::slug('Drama')],
            ['name' => 'Heartfelt', 'slug' => Str::slug('Heartfelt')],
            ['name' => 'Mystery', 'slug' => Str::slug('Mystery')]
        ];

        foreach ($categories as &$category) {
            $category['created_at'] = now();
            $category['updated_at'] = now();
        }

        DB::table('categories')->insert($categories);
    }
}
