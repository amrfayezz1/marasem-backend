<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Tag;

class TagsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = ['Abstract', 'Realism', 'Portrait', 'Landscape', 'Surrealism', 'Modern', 'Antique'];
        // get category ids from db
        $ids = \App\Models\Category::pluck('id')->toArray();
        foreach ($tags as $i => $tag) {
            Tag::create(['name' => $tag, 'category_id' => $ids[$i]]);
        }
    }
}
