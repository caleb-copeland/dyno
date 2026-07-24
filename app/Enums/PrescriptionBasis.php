<?php

namespace App\Enums;

/**
 * How a target is derived: a fixed number, a percentage of a tested max
 * (grip/pulling), or subjective effort (core/legs). Only grip and pulling give
 * a number worth computing percentages from (§7).
 */
enum PrescriptionBasis: string
{
    case Fixed = 'fixed';
    case PercentOfTest = 'percent_of_test';
    case Rpe = 'rpe';

    public function label(): string
    {
        return match ($this) {
            self::Fixed => 'Fixed target',
            self::PercentOfTest => '% of tested max',
            self::Rpe => 'RPE (effort)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
