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

    protected function handleRecordCreation(array $data): Model
    {
        try {
            return app(AppointmentService::class)->reserve($data);
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('Error in reserving appointment')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
