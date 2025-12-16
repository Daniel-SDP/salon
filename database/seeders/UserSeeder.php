<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $owner = new User();
        $owner->email = 'owner@gmail.com';
        $owner->name = 'Owner';
        $owner->password = bcrypt('12345678');
        $owner->save();

        $customer = new User();
        $customer->email = 'customer@gmail.com';
        $customer->name = 'customer';
        $customer->password = bcrypt('12345678');
        $customer->save();
    }
}
