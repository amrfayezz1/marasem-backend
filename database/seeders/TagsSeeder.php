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
        $tags = ['Abstract', 'Realism', 'Portrait', 'Landscape', 'Surrealism', 'Modern'];
        foreach ($tags as $tag) {
            Tag::create(['name' => $tag]);
        }
    }
}
