<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PromoCode;

class PromoCodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PromoCode::create([
            'code' => 'DISCOUNT10',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'expiry_date' => now()->addYear(),
        ]);
    }
}
