<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        // An admin editing themselves can't demote or deactivate — it would lock them out.
        $isSelf = fn (?User $record): bool => $record?->is(Auth::user()) ?? false;

        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true),

                Select::make('role')
                    ->options(['member' => 'Member', 'admin' => 'Admin'])
                    ->required()
                    ->disabled($isSelf)
                    ->helperText(fn (?User $record): ?string => $isSelf($record) ? "You can't change your own role." : null),

                Toggle::make('active')
                    ->label('Active')
                    ->disabled($isSelf)
                    ->helperText(fn (?User $record): string => $isSelf($record)
                        ? "You can't deactivate yourself."
                        : 'Inactive members are signed out and can\'t log in. Their history is kept.'),
            ]);
    }
}
