<?php

namespace App\Services;

use App\Enums\DayOfWeek;

/**
 * Assembles a training week. Deliberately NOT a general constraint solver — with
 * a handful of sessions across 7 days the space is tiny, so we enumerate valid
 * arrangements (pruning on the injury rules), score them on the soft rules, and
 * return the best few for "generate, then edit / shuffle".
 *
 * Rule tiers (brief §5):
 *   HARD (block): no finger-intensive training on consecutive days; cap total
 *   finger-intensive sessions at 3/week, COUNTING declared climbing days.
 *   SOFT (warn): grip & heavy pull apart, even spacing, don't stack high days;
 *   reward low-interference pairings (grip+legs, pull+core).
 */
class ScheduleGenerator
{
    /** Focus areas that load fingers maximally (climbing days are always finger days). */
    public const FINGER_FOCI = ['grip'];

    public const HEAVY_PULL = ['back'];

    public const HIGH_INTENSITY = ['grip', 'back'];

    /** Same-day pairings that interfere little and are preferred. */
    public const GOOD_PAIRS = [['grip', 'legs'], ['back', 'core']];

    public const MAX_FINGER_SESSIONS = 3;

    private int $nodeBudget = 200000;

    /**
     * @param  array<int>  $trainingDays   available day ints (0=Mon…6=Sun)
     * @param  array<int>  $climbingDays   declared climbing days (finger-maximal)
     * @param  array<string,int>  $frequencies  focus_area => sessions/week
     * @return array<int, array{sessions: array<int, array{focus:string, day:int}>, score: float, warnings: array<int,string>}>
     */
    public function generate(array $trainingDays, array $climbingDays, array $frequencies, int $limit = 5): array
    {
        $trainingDays = array_values(array_unique(array_map('intval', $trainingDays)));
        sort($trainingDays);
        $climbingDays = array_values(array_unique(array_map('intval', $climbingDays)));

        $sessions = [];
        foreach ($frequencies as $focus => $n) {
            for ($i = 0; $i < (int) $n; $i++) {
                $sessions[] = $focus;
            }
        }

        $arrangements = [];
        $this->nodeBudget = 200000;
        $this->place($sessions, 0, [], $trainingDays, $climbingDays, $arrangements);

        $scored = [];
        $seen = [];
        foreach ($arrangements as $arr) {
            $sig = $this->signature($arr);
            if (isset($seen[$sig])) {
                continue;
            }
            $seen[$sig] = true;

            $issues = $this->issues($arr, $climbingDays);
            if (! empty($issues['hard'])) {
                continue; // enumeration already prunes these; belt and braces
            }

            $scored[] = [
                'sessions' => $arr,
                'score' => $this->score($arr, $climbingDays),
                'warnings' => $issues['soft'],
            ];
        }

        usort($scored, fn ($a, $b) => $a['score'] <=> $b['score']);

        return array_slice($scored, 0, $limit);
    }

    /**
     * Evaluate an arbitrary (e.g. user-edited) arrangement.
     *
     * @param  array<int, array{focus:string, day:int}>  $placements
     * @param  array<int>  $climbingDays
     * @return array{hard: array<int,string>, soft: array<int,string>}
     */
    public function issues(array $placements, array $climbingDays): array
    {
        $hard = [];
        $soft = [];

        $fingerDays = $this->fingerDays($placements, $climbingDays);

        // HARD: no finger-intensive training on consecutive days (cyclic week).
        foreach ($fingerDays as $d) {
            $next = ($d + 1) % 7;
            if (in_array($next, $fingerDays, true)) {
                $hard[] = 'Finger-intensive load on consecutive days ('
                    .DayOfWeek::from($d)->short().' → '.DayOfWeek::from($next)->short()
                    .') — high A2 pulley injury risk.';
            }
        }

        // HARD: cap total finger sessions (climbing days + finger training) at 3.
        $fingerSessions = count($climbingDays) + count($this->fingerPlacements($placements));
        if ($fingerSessions > self::MAX_FINGER_SESSIONS) {
            $hard[] = "Too many finger-intensive sessions this week ({$fingerSessions}); cap is "
                .self::MAX_FINGER_SESSIONS.' including climbing days.';
        }

        // SOFT: grip and heavy pull on the same day.
        foreach ($this->byDay($placements) as $day => $foci) {
            if (array_intersect(self::FINGER_FOCI, $foci) && array_intersect(self::HEAVY_PULL, $foci)) {
                $soft[] = 'Grip and heavy pulling on the same day ('.DayOfWeek::from($day)->short().').';
            }
        }

        // SOFT: two high-intensity days back-to-back.
        $highDays = $this->highIntensityDays($placements);
        foreach ($highDays as $d) {
            if (in_array(($d + 1) % 7, $highDays, true)) {
                $soft[] = 'Two high-intensity days stacked ('
                    .DayOfWeek::from($d)->short().' → '.DayOfWeek::from(($d + 1) % 7)->short().').';
            }
        }

        // SOFT: uneven spacing within a focus trained 2+ times.
        foreach ($this->focusDays($placements) as $focus => $days) {
            if (count($days) >= 2 && $this->minCyclicGap($days) < 2) {
                $soft[] = ucfirst($focus).' sessions are close together — spread them out.';
            }
        }

        return ['hard' => array_values(array_unique($hard)), 'soft' => array_values(array_unique($soft))];
    }

    /**
     * @param  array<int>  $sessions  flat list of focus strings still to place — indexed by $i
     * @param  array<int, array{focus:string, day:int}>  $current
     * @param  array<int>  $trainingDays
     * @param  array<int>  $climbingDays
     * @param  array<int, array<int, array{focus:string, day:int}>>  $out
     */
    private function place(array $sessions, int $i, array $current, array $trainingDays, array $climbingDays, array &$out): void
    {
        if ($this->nodeBudget-- <= 0 || count($out) >= 400) {
            return;
        }
        if ($i >= count($sessions)) {
            $out[] = $current;

            return;
        }

        $focus = $sessions[$i];
        foreach ($trainingDays as $day) {
            $onDay = array_filter($current, fn ($p) => $p['day'] === $day);
            if (count($onDay) >= 2) {
                continue; // at most 2 sessions/day
            }
            if (array_filter($onDay, fn ($p) => $p['focus'] === $focus)) {
                continue; // no duplicate focus on the same day
            }

            $cand = array_merge($current, [['focus' => $focus, 'day' => $day]]);
            if (! empty($this->issues($cand, $climbingDays)['hard'])) {
                continue; // prune on hard-rule violation
            }

            $this->place($sessions, $i + 1, $cand, $trainingDays, $climbingDays, $out);
        }
    }

    /** @param array<int, array{focus:string, day:int}> $placements */
    private function score(array $placements, array $climbingDays): float
    {
        $score = 0.0;
        $issues = $this->issues($placements, $climbingDays);
        $score += 5.0 * count($issues['soft']);

        // Reward preferred low-interference same-day pairings.
        foreach ($this->byDay($placements) as $foci) {
            foreach (self::GOOD_PAIRS as [$a, $b]) {
                if (in_array($a, $foci, true) && in_array($b, $foci, true)) {
                    $score -= 1.5;
                }
            }
        }

        // Prefer spreading sessions across days (fewer doubled-up days).
        foreach ($this->byDay($placements) as $foci) {
            if (count($foci) > 1) {
                $score += 0.5;
            }
        }

        // Reward even spacing within each focus (lower total variance = better).
        foreach ($this->focusDays($placements) as $days) {
            if (count($days) >= 2) {
                $score += $this->spacingPenalty($days);
            }
        }

        return $score;
    }

    /** @param array<int, array{focus:string, day:int}> $placements @return array<int> */
    private function fingerPlacements(array $placements): array
    {
        return array_values(array_filter(
            array_map(fn ($p) => in_array($p['focus'], self::FINGER_FOCI, true) ? $p['day'] : null, $placements),
            fn ($d) => $d !== null,
        ));
    }

    /** @return array<int> distinct days carrying finger load (training + climbing) */
    private function fingerDays(array $placements, array $climbingDays): array
    {
        return array_values(array_unique(array_merge($this->fingerPlacements($placements), $climbingDays)));
    }

    /** @return array<int> distinct days with a high-intensity session */
    private function highIntensityDays(array $placements): array
    {
        $days = [];
        foreach ($placements as $p) {
            if (in_array($p['focus'], self::HIGH_INTENSITY, true)) {
                $days[] = $p['day'];
            }
        }

        return array_values(array_unique($days));
    }

    /** @return array<int, array<int,string>> day => focus[] */
    private function byDay(array $placements): array
    {
        $map = [];
        foreach ($placements as $p) {
            $map[$p['day']][] = $p['focus'];
        }

        return $map;
    }

    /** @return array<string, array<int,int>> focus => day[] */
    private function focusDays(array $placements): array
    {
        $map = [];
        foreach ($placements as $p) {
            $map[$p['focus']][] = $p['day'];
        }

        return $map;
    }

    /** @param array<int,int> $days */
    private function minCyclicGap(array $days): int
    {
        sort($days);
        $min = 7;
        $n = count($days);
        for ($i = 0; $i < $n; $i++) {
            $gap = $i + 1 < $n ? $days[$i + 1] - $days[$i] : (7 - $days[$i]) + $days[0];
            $min = min($min, $gap);
        }

        return $min;
    }

    /** @param array<int,int> $days */
    private function spacingPenalty(array $days): float
    {
        sort($days);
        $n = count($days);
        $ideal = 7 / $n;
        $gaps = [];
        for ($i = 0; $i < $n; $i++) {
            $gaps[] = $i + 1 < $n ? $days[$i + 1] - $days[$i] : (7 - $days[$i]) + $days[0];
        }
        $penalty = 0.0;
        foreach ($gaps as $g) {
            $penalty += abs($g - $ideal);
        }

        return $penalty * 0.5;
    }

    /** @param array<int, array{focus:string, day:int}> $placements */
    private function signature(array $placements): string
    {
        $pairs = array_map(fn ($p) => $p['day'].':'.$p['focus'], $placements);
        sort($pairs);

        return implode('|', $pairs);
    }
}
