<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WorkingHour;
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

        $this->call([
            UserSeeder::class,
            SalonSeeder::class,
            ServiceSeeder::class,
            EmployeeSeeder::class,
            WorkingHourSeeder::class,
            AppointmentSeeder::class,
        ]);
    }
}
