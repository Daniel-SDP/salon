<?php

namespace Database\Seeders;

use App\Models\Salon;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SalonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = User::where('email', 'owner@gmail.com')->first();

        $salon = new Salon();
        $salon->name = 'Salon 1';
        $salon->phone = '09122222222';
        $salon->address = 'Birjand';
        $salon->owner_id = $owner->id;
        $salon->save();
    }
}
