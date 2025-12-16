<?php

namespace App\Filament\Resources\Salons\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SalonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Salon Name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('phone')
                    ->label('Salon Phone'),

                TextInput::make('address')
                    ->label('Salon Address'),

                Select::make('owner_id')
                    ->label('Owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->required()
            ]);
    }
}
