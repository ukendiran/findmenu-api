<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Business;

class UpdateFeaturedBusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Update first 6 businesses to be featured with placeholder logos
        $businesses = Business::limit(6)->get();
        
        $logos = [
            'mcdonalds.png',
            'starbucks.png',
            'subway.png',
            'kfc.png',
            'pizzahut.png',
            'dominos.png',
        ];

        foreach ($businesses as $index => $business) {
            $business->update([
                'status' => 1,
                'is_featured' => 1,
                'logo' => $logos[$index] ?? 'logo.png',
            ]);
            
            $this->command->info("Updated business: {$business->name} to featured");
        }

        $count = $businesses->count();
        $this->command->info("Updated {$count} businesses to featured!");
    }
}
