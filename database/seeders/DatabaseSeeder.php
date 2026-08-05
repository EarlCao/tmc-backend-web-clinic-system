<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        // Default TMC administrator — matches the email shown on the login
        // page. The password is hashed by the model's `hashed` cast and is
        // never returned by the API.
        User::factory()->create([
            'name' => 'TMC Administrator',
            'email' => 'admin@tmc.edu.ph',
            'password' => 'admin123',
            'role' => 'admin',
        ]);
    }
}
