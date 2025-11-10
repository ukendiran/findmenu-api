<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;
use Illuminate\Support\Facades\DB;

class FeaturedBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $featuredBusinesses = [
            [
                'name' => 'McDonald\'s India',
                'email' => 'contact@mcdonalds.in',
                'code' => 'mcdonalds-india',
                'mobile' => '+91-1234567890',
                'address' => 'Mumbai, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/3/36/McDonald%27s_Golden_Arches.svg/200px-McDonald%27s_Golden_Arches.svg.png',
                'type' => 'restaurant',
                'status' => 1,
                'is_featured' => 1,
            ],
            [
                'name' => 'Starbucks Coffee',
                'email' => 'info@starbucks.in',
                'code' => 'starbucks-coffee',
                'mobile' => '+91-1234567891',
                'address' => 'Delhi, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/d/d3/Starbucks_Corporation_Logo_2011.svg/200px-Starbucks_Corporation_Logo_2011.svg.png',
                'type' => 'cafe',
                'status' => 1,
                'is_featured' => 1,
            ],
            [
                'name' => 'Subway India',
                'email' => 'contact@subway.in',
                'code' => 'subway-india',
                'mobile' => '+91-1234567892',
                'address' => 'Bangalore, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/5/5c/Subway_2016_logo.svg/200px-Subway_2016_logo.svg.png',
                'type' => 'restaurant',
                'status' => 1,
                'is_featured' => 1,
            ],
            [
                'name' => 'KFC India',
                'email' => 'info@kfc.in',
                'code' => 'kfc-india',
                'mobile' => '+91-1234567893',
                'address' => 'Chennai, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/b/bf/KFC_logo.svg/200px-KFC_logo.svg.png',
                'type' => 'restaurant',
                'status' => 1,
                'is_featured' => 1,
            ],
            [
                'name' => 'Pizza Hut India',
                'email' => 'contact@pizzahut.in',
                'code' => 'pizza-hut-india',
                'mobile' => '+91-1234567894',
                'address' => 'Pune, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/en/thumb/d/d2/Pizza_Hut_logo.svg/200px-Pizza_Hut_logo.svg.png',
                'type' => 'restaurant',
                'status' => 1,
                'is_featured' => 1,
            ],
            [
                'name' => 'Domino\'s Pizza',
                'email' => 'info@dominos.in',
                'code' => 'dominos-pizza',
                'mobile' => '+91-1234567895',
                'address' => 'Hyderabad, India',
                'logo' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/7/74/Dominos_pizza_logo.svg/200px-Dominos_pizza_logo.svg.png',
                'type' => 'restaurant',
                'status' => 1,
                'is_featured' => 1,
            ],
        ];

        foreach ($featuredBusinesses as $business) {
            // Check if business already exists
            $exists = Business::where('code', $business['code'])->exists();
            
            if (!$exists) {
                Business::create($business);
                $this->command->info("Created featured business: {$business['name']}");
            } else {
                $this->command->info("Business already exists: {$business['name']}");
            }
        }

        $this->command->info('Featured businesses seeded successfully!');
    }
}
