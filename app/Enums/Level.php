<?php

namespace App\Enums;

enum Level: string
{
    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function filamentColor(): string
    {
        return match ($this) {
            self::Beginner => 'success',
            self::Intermediate => 'warning',
            self::Advanced => 'danger',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
