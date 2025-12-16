<?php

namespace App\Filament\Resources\Appointments\Pages;

use App\Filament\Resources\Appointments\AppointmentResource;
use App\Models\Service;
use App\Services\AppointmentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;


class CreateAppointment extends CreateRecord
{
    protected static string $resource = AppointmentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = Service::find($data['service_id']);

        $data['end_time'] = now()
            ->setTimeFromTimeString($data['start_time'])
            ->addMinutes($service->duration)
            ->format('H:i');

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AppointmentService::class)->create($data);
        } catch (\Exception $e) {
            Notification::make()
                ->title('خطا در ثبت رزرو')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
