<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Event;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Event::create([
            'title' => 'Art Gala 2024',
            'description' => 'A grand exhibition of contemporary art featuring renowned artists.',
            'date_start' => '2025-05-10',
            'date_end' => '2025-05-12',
            'time_start' => '10:00:00',
            'time_end' => '18:00:00',
            'location' => 'Cairo Opera House',
            'location_url' => 'https://maps.google.com/example',
            'cover_img_path' => 'https://example.com/art-gala.jpg',
            'status' => 'upcoming',
            'expires' => '2025-05-13',
        ]);

        Event::create([
            'title' => 'Photography Workshop',
            'description' => 'A hands-on workshop to master the art of photography.',
            'date_start' => '2025-03-15',
            'date_end' => '2025-03-16',
            'time_start' => '09:00:00',
            'time_end' => '17:00:00',
            'location' => 'Alexandria Library',
            'location_url' => 'https://maps.google.com/example2',
            'cover_img_path' => 'https://example.com/photography-workshop.jpg',
            'status' => 'upcoming',
            'expires' => '2025-03-17',
        ]);
    }
}
