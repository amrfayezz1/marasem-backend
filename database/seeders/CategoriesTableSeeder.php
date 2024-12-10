<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        // Insert parent categories
        $parent1 = DB::table('categories')->insertGetId([
            'name' => 'Art',
            'parent_id' => null,
        ]);

        $parent2 = DB::table('categories')->insertGetId([
            'name' => 'Craft',
            'parent_id' => null,
        ]);

        // Insert sub-categories under 'Art'
        DB::table('categories')->insert([
            ['name' => 'Painting', 'parent_id' => $parent1],
            ['name' => 'Sculpture', 'parent_id' => $parent1],
            ['name' => 'Photography', 'parent_id' => $parent1],
        ]);

        // Insert sub-categories under 'Craft'
        DB::table('categories')->insert([
            ['name' => 'Woodworking', 'parent_id' => $parent2],
            ['name' => 'Pottery', 'parent_id' => $parent2],
            ['name' => 'Textile Art', 'parent_id' => $parent2],
        ]);
    }
}
