<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@findmenu.com',
            'password' => Hash::make('superadmin123'),
            'mobile' => '+1234567890',
            'phone' => '+1234567890',
            'gender' => 'Male',
            'status' => 1,
            'role' => 'superadmin',
            'email_verified_at' => now(),
        ]);

        $this->command->info('Super admin user created successfully!');
        $this->command->info('Email: superadmin@findmenu.com');
        $this->command->info('Password: superadmin123');
    }
}
