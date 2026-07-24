<?php

namespace App\Enums;

/**
 * What an exercise measures — drives which target fields and which runner UI
 * (a plain set vs. an interval protocol) apply.
 */
enum PrescriptionType: string
{
    case Reps = 'reps';
    case Time = 'time';
    case Weight = 'weight';
    case Interval = 'interval';

    public function label(): string
    {
        return match ($this) {
            self::Reps => 'Reps',
            self::Time => 'Time (hold)',
            self::Weight => 'Weight',
            self::Interval => 'Interval (hangboard)',
        };
    }

    /** Interval prescriptions use the repeat/rest interval timer, not a plain set. */
    public function usesIntervalTimer(): bool
    {
        return $this === self::Interval;
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
