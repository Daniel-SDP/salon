<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\Service;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salon = Salon::first();

        Service::insert([
            [
                'salon_id' => $salon->id,
                'name' => 'Haircut',
                'price' => 150000,
                'duration' => 30,
                'buffer_minutes' => 10,
                'capacity' => 1,
            ],
            [
                'salon_id' => $salon->id,
                'name' => 'Hair Coloring',
                'price' => 400000,
                'duration' => 60,
                'buffer_minutes' => 20,
                'capacity' => 2,
            ]
        ]);
    }
}
