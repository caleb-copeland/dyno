<?php

namespace App\Enums;

/**
 * The strength focus areas. `push` (antagonist) is included deliberately:
 * the other five are all pull-dominant, and climbers who only train pulling
 * develop shoulder problems — so antagonist work is a first-class area, not
 * folded invisibly into other sessions.
 *
 * Each area owns one semantic accent colour, used consistently everywhere the
 * area appears (§9) — a wayfinding system, not decoration.
 */
enum FocusArea: string
{
    case Grip = 'grip';
    case Core = 'core';
    case Back = 'back';
    case Arms = 'arms';
    case Legs = 'legs';
    case Push = 'push';

    public function label(): string
    {
        return match ($this) {
            self::Grip => 'Grip',
            self::Core => 'Core',
            self::Back => 'Back',
            self::Arms => 'Arms',
            self::Legs => 'Legs',
            self::Push => 'Push / Antagonist',
        };
    }

    /** Semantic accent hex for the PWA (OLED-dark UI). Distinguishable hues. */
    public function accentHex(): string
    {
        return match ($this) {
            self::Grip => '#F5A524', // amber
            self::Core => '#3B82F6', // blue
            self::Back => '#22C55E', // green
            self::Arms => '#A855F7', // purple
            self::Legs => '#14B8A6', // teal
            self::Push => '#EF4444', // red
        };
    }

    /** Filament badge colour name for the admin panel. */
    public function filamentColor(): string
    {
        return match ($this) {
            self::Grip => 'warning',
            self::Core => 'info',
            self::Back => 'success',
            self::Arms => 'purple',
            self::Legs => 'teal',
            self::Push => 'danger',
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
