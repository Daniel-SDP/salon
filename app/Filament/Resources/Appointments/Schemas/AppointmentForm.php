<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Employee;
use App\Models\Service;
use App\Services\AvailableTimeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Wizard::make()
                    ->columnSpanFull()
                    ->steps([
                        Wizard\Step::make('Select Salon & Service')
                            ->schema([
                                Select::make('salon_id')
                                    ->label('Salon')
                                    ->relationship('salon', 'name')
                                    ->reactive()
                                    ->required()
                                    ->afterStateHydrated(fn ($state, callable $set, $record) => $set('salon_id', $record?->salon_id))
                                    ->afterStateUpdated(fn ($set) => $set('service_id', null)),

                                Select::make('service_id')
                                    ->label('Service')
                                    ->options(fn (callable $get) => $get('salon_id') ? Service::where('salon_id', $get('salon_id'))->pluck('name', 'id') : [])
                                    ->reactive()
                                    ->required()
                                    ->afterStateHydrated(fn ($state, callable $set, $record) => $set('service_id', $record?->service_id))
                                    ->afterStateUpdated(fn ($set) => $set('employee_id', null)),
                            ]),

                        Wizard\Step::make('Select Employee & Date')
                            ->schema([
                                Select::make('employee_id')
                                    ->label('Employee')
                                    ->options(fn (callable $get) => $get('salon_id') && $get('service_id') ? Employee::where('salon_id', $get('salon_id'))->where('is_active', true)->pluck('name','id') : [])
                                    ->reactive()
                                    ->required()
                                    ->afterStateHydrated(fn ($state, callable $set, $record) => $set('employee_id', $record?->employee_id))
                                    ->afterStateUpdated(fn ($set) => $set('date', null)),

                                DatePicker::make('date')
                                    ->required()
                                    ->reactive()
                                    ->afterStateHydrated(fn ($state, callable $set, $record) => $set('date', $record?->date)),
                            ]),

                        Wizard\Step::make('Select Time')
                            ->schema([
                                Select::make('start_time')
                                    ->label('Start Time')
                                    ->options(function (callable $get, $record = null) {
                                        $employeeId = $get('employee_id');
                                        $date = $get('date');
                                        $serviceId = $get('service_id');

                                        if (!$employeeId || !$date || !$serviceId) {
                                            return [];
                                        }

                                        $service = \App\Models\Service::find($serviceId);
                                        $serviceDuration = $service?->duration ?? 30;

                                        $availableTimes = (new \App\Services\AvailableTimeService())
                                            ->getAvailableTimes($employeeId, $date, $serviceDuration, $serviceId);

                                        $options = collect($availableTimes)->mapWithKeys(fn($slot) => [
                                            $slot['start'] => $slot['start'] . '-' . $slot['end'] . ' (' . ($service->buffer_minutes ?? 0) . ' min gap)',
                                        ])->toArray();

                                        // اضافه کردن مقدار قبلی record اگر وجود داشته باشد
                                        if ($record?->start_time && !isset($options[$record->start_time])) {
                                            $options = [$record->start_time => $record->start_time . ' (current)'] + $options;
                                        }

                                        return $options;
                                    })
                                    ->default(fn($record) => $record?->start_time)
                                    ->reactive()
                                    ->required()
                        ]),

                        Wizard\Step::make('Confirm & Book')
                            ->schema([
                                Select::make('user_id')
                                    ->label('Your name')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->required(),
                            ]),
                    ]),
            ]);
    }

    public static function getAvailableTimesForForm(callable $get): array
    {
        $employeeId = $get('employee_id');
        $date = $get('date');
        $serviceId = $get('service_id');

        if (!$employeeId || !$date || !$serviceId) return [];

        $service = Service::find($serviceId);
        $serviceDuration = $service?->duration ?? 30;

        $availableTimes = (new AvailableTimeService())->getAvailableTimes(
            $employeeId,
            $date,
            $serviceDuration,
            $serviceId
        );

        return collect($availableTimes)->mapWithKeys(fn($slot) => [
            $slot['start'] => $slot['start'].'-'.$slot['end'].' ('.($service->buffer_minutes ?? 0).' min gap)',
        ])->toArray();
    }
}
