<?php

namespace App\Filament\Resources\Appointments\Schemas;

use App\Models\Employee;
use App\Models\Service;
use App\Models\Appointment;
use App\Services\AvailableTimeService;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Radio;
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
                                    ->searchable()
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(fn ($set) => $set('service_id', null)),

                                Select::make('service_id')
                                    ->label('Service')
                                    ->options(fn (callable $get) => $get('salon_id') ? Service::where('salon_id', $get('salon_id'))->pluck('name', 'id') : [])
                                    ->searchable()
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(fn ($set) => $set('employee_id', null)),
                            ]),

                        Wizard\Step::make('Select Employee & Date')
                            ->schema([
                                Select::make('employee_id')
                                    ->label('Employee')
                                    ->options(fn (callable $get) => $get('salon_id') && $get('service_id') ? Employee::where('salon_id', $get('salon_id'))->where('is_active', true)->pluck('name','id') : [])
                                    ->reactive()
                                    ->required()
                                    ->afterStateUpdated(fn ($set) => $set('date', null)),

                                DatePicker::make('date')
                                    ->required()
                                    ->reactive(),
                            ]),

                        Wizard\Step::make('Select Time')
                            ->schema([
                                Radio::make('start_time')
                                    ->label('Start Time')
                                    ->options(function (callable $get, $record = null) {
                                        $employeeId = $get('employee_id');
                                        $date = $get('date');
                                        $serviceId = $get('service_id');

                                        if (!$employeeId || !$date || !$serviceId) {
                                            return [];
                                        }

                                        $service = Service::find($serviceId);
                                        $serviceDuration = $service?->duration ?? 30;
                                        $capacity = $service?->capacity ?? 1;

                                        $availableTimes = (new AvailableTimeService())
                                            ->getAvailableTimes($employeeId, $date, $serviceDuration, $serviceId);

                                        $options = collect($availableTimes)->mapWithKeys(function ($slot) use ($service, $employeeId, $date, $capacity) {
                                            // تعداد رزروهای موجود
                                            $booked = Appointment::where('employee_id', $employeeId)
                                                ->where('date', $date)
                                                ->where('status', '!=', 'canceled')
                                                ->where('start_time', $slot['start'])
                                                ->count();

                                            // رنگ‌بندی یا متن وضعیت
                                            $label = $slot['start'] . '-' . $slot['end'] . ' (' . ($service->buffer_minutes ?? 0) . ' min gap)';
                                            if ($booked >= $capacity) {
                                                $label .= ' - Full';
                                            } elseif ($booked > 0) {
                                                $label .= " - {$booked}/{$capacity} booked";
                                            }

                                            return [$slot['start'] => $label];
                                        })->toArray();

                                        // اضافه کردن مقدار قبلی record اگر وجود داشته باشد
                                        if ($record?->start_time && !isset($options[$record->start_time])) {
                                            $options = [$record->start_time => $record->start_time . ' (current)'] + $options;
                                        }

                                        return $options;
                                    })
                                    ->default(fn($record) => $record?->start_time)
                                    ->reactive()
                                    ->required(),
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
}
