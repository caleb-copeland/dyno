<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * role/active aren't mass-assignable, so the default fill() would drop them
     * silently. Set them explicitly — and never for the acting admin, even if a
     * tampered request re-enables the disabled fields.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->fill(Arr::except($data, ['role', 'active']));

        if (! $record->is(Auth::user())) {
            $record->forceFill(Arr::only($data, ['role', 'active']));
        }

        $record->save();

        return $record;
    }
}
