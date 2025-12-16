<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\WorkingHour;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WorkingHourSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employee = Employee::first();

        foreach ([6, 0, 1, 2, 3] as $day) {
            $workingHour = new WorkingHour();
            $workingHour->employee_id = $employee->id;
            $workingHour->day_of_week = $day;
            $workingHour->start_time = '09:00:00';
            $workingHour->end_time = '17:00:00';
            $workingHour->save();
        }
    }
}
