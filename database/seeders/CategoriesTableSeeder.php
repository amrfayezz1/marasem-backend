<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesTableSeeder extends Seeder
{
    public function run()
    {
        DB::table('categories')->insert([
            ['name' => 'Art'],
            ['name' => 'Craft'],
            ['name' => 'Painting'],
            ['name' => 'Sculpture'],
            ['name' => 'Photography'],
            ['name' => 'Woodworking'],
            ['name' => 'Pottery'],
            ['name' => 'Textile Art'],
        ]);
    }
}
