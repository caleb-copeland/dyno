<?php

namespace App\Filament\Resources\Invites\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InviteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->label('Invitee email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->helperText('The registration link will only work for this address.'),

                DateTimePicker::make('expires_at')
                    ->label('Expires at')
                    ->default(now()->addDays(14))
                    ->required()
                    ->helperText('After this time the link stops working.'),
            ]);
    }
}
