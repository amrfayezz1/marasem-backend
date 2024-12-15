<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Collection;

class CollectionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ids = \App\Models\Tag::pluck('id')->toArray();
        $collections = [
            ['title' => 'Nature Vibes', 'tags' => json_encode([$ids[1], $ids[2]]), 'followers' => 0],
            ['title' => 'Abstract Art', 'tags' => json_encode([$ids[3]]), 'followers' => 0],
        ];

        foreach ($collections as $collection) {
            Collection::create($collection);
        }
    }
}
