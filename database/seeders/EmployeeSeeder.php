<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Salon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EmployeeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $salon = Salon::first();

        $employee = new Employee();
        $employee->name = 'Ali Ahmadi';
        $employee->phone = '09123334444';
        $employee->is_active = true;
        $employee->salon_id = $salon->id;
        $employee->save();
    }
}
