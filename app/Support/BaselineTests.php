<?php

namespace App\Support;

use App\Enums\FocusArea;

/**
 * The gated baseline tests. Only grip and pulling give a number worth computing
 * percentages from (§7) — core/legs use RPE / progression ladders, so they have
 * no test here. Max tests are themselves an injury vector, so each is gated
 * behind a minimum number of logged sessions and carries a scripted warmup.
 */
class BaselineTests
{
    /**
     * @return array<string, array{
     *   key:string, focus:string, label:string, unit:string, min_sessions:int,
     *   retest_days:int, description:string, warmup:array<int,string>
     * }>
     */
    public static function all(): array
    {
        return [
            'grip_max_hang' => [
                'key' => 'grip_max_hang',
                'focus' => FocusArea::Grip->value,
                'label' => 'Max Hang — 20mm edge',
                'unit' => 'kg',
                'min_sessions' => 6,
                'retest_days' => 42,
                'description' => 'Added weight for a 10s hang on a 20mm edge, half-crimp or open hand. Drives % -of-max grip prescriptions.',
                'warmup' => [
                    '5 min light cardio / arm circles to raise tissue temperature.',
                    '3 × 10s easy hangs on a large hold, bodyweight, 2 min rest.',
                    '2 × 7s hangs on the 20mm edge at ~50% of expected added weight.',
                    '1 × 5s primer at ~80% of expected added weight, then rest 3 min.',
                ],
            ],
            'back_weighted_pullup' => [
                'key' => 'back_weighted_pullup',
                'focus' => FocusArea::Back->value,
                'label' => 'Weighted Pull-up — 1RM',
                'unit' => 'kg',
                'min_sessions' => 4,
                'retest_days' => 42,
                'description' => 'Heaviest single full-range weighted pull-up. Drives % -of-max pulling prescriptions.',
                'warmup' => [
                    '5 min light cardio; shoulder band work.',
                    '8 bodyweight pull-ups, controlled.',
                    '3 reps at ~50% of expected added weight, 2 min rest.',
                    '1 rep at ~85% of expected added weight, then rest 3 min.',
                ],
            ],
        ];
    }

    /** @return array<string, mixed>|null */
    public static function find(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /** The test whose result feeds % -of-test prescriptions for a focus area. */
    public static function forFocus(FocusArea $focus): ?string
    {
        foreach (static::all() as $key => $def) {
            if ($def['focus'] === $focus->value) {
                return $key;
            }
        }

        return null;
    }
}
