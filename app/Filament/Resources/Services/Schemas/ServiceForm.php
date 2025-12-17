<?php

namespace App\Filament\Resources\Services\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ServiceForm
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
                    ->label('Service')
                    ->required(),

                TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->required(),

                TextInput::make('duration')
                    ->label('Duration')
                    ->numeric()
                    ->required(),

                TextInput::make('capacity')
                    ->label('Capacity')
                    ->numeric()
                    ->minValue(1)
                    ->default(1),
            ]);
    }
}
