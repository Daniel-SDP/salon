<?php

namespace App\Filament\Resources\Employees\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('salon_id')
                    ->label('Salon')
                    ->relationship('salon', 'name')
                    ->required(),

                TextInput::make('name')
                    ->label('Name')
                    ->required(),

                TextInput::make('phone')
                    ->label('Phone'),

                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true),

                Select::make('slot_strategy')
                    ->label('Slot Strategy')
                    ->options([
                        'service' => 'Overlap-friendly',
                        'total'   => 'Non-overlapping',
                    ])
                    ->default('service')
            ]);
    }
}
