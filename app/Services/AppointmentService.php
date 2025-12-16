<?php

namespace App\Services;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Exception;

class AppointmentService
{
    public function create(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            $service = Service::findOrFail($data['service_id']);

            $data['end_time'] = now()
                ->setTimeFromTimeString($data['start_time'])
                ->addMinutes($service->duration)
                ->format('H:i');

            if (Appointment::hasOverlap(
                $data['employee_id'],
                $data['date'],
                $data['start_time'],
                $data['end_time']
            )) {
                throw new Exception('این بازه زمانی قبلاً رزرو شده است.');
            }

            return Appointment::create($data);
        });
    }
}
