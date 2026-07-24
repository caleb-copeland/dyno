<?php

namespace App\Enums;

/**
 * 0 = Monday … 6 = Sunday. A training week, not Carbon's Sunday-first index.
 */
enum DayOfWeek: int
{
    case Monday = 0;
    case Tuesday = 1;
    case Wednesday = 2;
    case Thursday = 3;
    case Friday = 4;
    case Saturday = 5;
    case Sunday = 6;

    public function short(): string
    {
        return ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'][$this->value];
    }

    public function label(): string
    {
        return ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'][$this->value];
    }

    /** @return array<int, string> day int => short label */
    public static function shortMap(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $d) => [$d->value => $d->short()])->all();
    }
}
