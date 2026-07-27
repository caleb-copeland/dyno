<?php

namespace App\Enums;

/**
 * The strength focus areas. All four are pull/climbing-dominant.
 *
 * Each area owns one semantic accent colour, used consistently everywhere the
 * area appears (§9) — a wayfinding system, not decoration.
 */
enum FocusArea: string
{
    case Grip = 'grip';
    case Pullups = 'pullups';
    case Core = 'core';
    case Legs = 'legs';

    public function label(): string
    {
        return match ($this) {
            self::Grip => 'Grip',
            self::Pullups => 'Pull-ups',
            self::Core => 'Core',
            self::Legs => 'Legs',
        };
    }

    /** Semantic accent hex for the PWA (OLED-dark UI). Distinguishable hues. */
    public function accentHex(): string
    {
        return match ($this) {
            self::Grip => '#F5A524',    // amber
            self::Pullups => '#22C55E', // green
            self::Core => '#3B82F6',    // blue
            self::Legs => '#14B8A6',    // teal
        };
    }

    /** Filament badge colour name for the admin panel. */
    public function filamentColor(): string
    {
        return match ($this) {
            self::Grip => 'warning',
            self::Pullups => 'success',
            self::Core => 'info',
            self::Legs => 'teal',
        };
    }

    /** @return array<string, string> value => label, for Filament option lists. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $c) => [$c->value => $c->label()])
            ->all();
    }
}
