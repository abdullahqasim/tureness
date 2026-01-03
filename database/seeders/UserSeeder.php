<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create 10 users with a dummy password 'password123'
        User::factory()->count(10)->create([
            'password' => Hash::make('password123'),
        ]);
    }
}
