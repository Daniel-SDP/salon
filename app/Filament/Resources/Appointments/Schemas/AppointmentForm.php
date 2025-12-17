<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Employee;
use App\Models\Service;
use App\Services\AvailableTimeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class AppointmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->required(),

                Select::make('salon_id')
                    ->label('Salon')
                    ->relationship('salon', 'name')
                    ->reactive()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($set) => $set('service_id', null)),

                Select::make('service_id')
                    ->label('Service')
                    ->options(function (callable $get){
                        $salonId = $get('salon_id');
                        if( !$salonId )
                            return [];

                        return Service::where('salon_id', $salonId)->pluck('name', 'id')->toArray();
                    })
                    ->reactive()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($set) => $set('employee_id', null))
                    ->disabled(fn (callable $get) => !$get('salon_id')),

                Select::make('employee_id')
                    ->label('Employee')
                    ->options(function (callable $get){
                        $salonId = $get('salon_id');
                        if( !$salonId )
                            return [];

                        return Employee::where('salon_id', $salonId)->where('is_active', true)->pluck('name', 'id')->toArray();
                    })
                    ->reactive()
                    ->live()
                    ->required()
                    ->afterStateUpdated(fn ($set) => $set('start_time', null))
                    ->disabled(fn (callable $get) => !$get('service_id')),

                DatePicker::make('date')
                    ->reactive()
                    ->live()
                    ->required(),

                Select::make('start_time')
                    ->label('Start Time')
                    ->options(function (callable $get){
                        $employeeId = $get('employee_id');
                        $date = $get('date');
                        $serviceId = $get('service_id');

                        if(!$employeeId || !$date || !$serviceId )
                            return [];

                        $service = Service::find($serviceId);
                        $serviceDuration = $service?->duraion ?? 30;

                        $availableTimes = (new AvailableTimeService())->getAvailableTimes($employeeId, $date, $serviceDuration, $serviceId);
                        return collect($availableTimes)->mapWithKeys(function ($slot) use ($service) {
                            return [
                                $slot['start'] => $slot['start'] . '-' . $slot['end'] . ' (including ' . $service->buffer_minutes . ' minute gap)',
                            ];
                        })->toArray();
                    })
                    ->reactive()
                    ->live()
                    ->required()
                    ->disabled(fn (callable $get) =>
                        !$get('employee_id') || !$get('date') || !$get('service_id')
                    ),

                Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'confirmed' => 'Confirmed',
                        'canceled' => 'Canceled',
                    ])
                    ->required(),
            ]);
    }
}
