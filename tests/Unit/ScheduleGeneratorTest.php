<?php

namespace Tests\Unit;

use App\Services\ScheduleGenerator;
use PHPUnit\Framework\TestCase;

class ScheduleGeneratorTest extends TestCase
{
    private ScheduleGenerator $gen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gen = new ScheduleGenerator();
    }

    /** @param array<int, array{focus:string, day:int}> $placements */
    private function fingerDays(array $placements, array $climbing): array
    {
        $days = $climbing;
        foreach ($placements as $p) {
            if (in_array($p['focus'], ScheduleGenerator::FINGER_FOCI, true)) {
                $days[] = $p['day'];
            }
        }

        return array_values(array_unique($days));
    }

    public function test_every_result_obeys_the_hard_rules(): void
    {
        // Mon,Tue,Wed,Thu,Fri available; climb Sat. grip x2, back x2, core x1, legs x1.
        $results = $this->gen->generate(
            trainingDays: [0, 1, 2, 3, 4],
            climbingDays: [5],
            frequencies: ['grip' => 2, 'back' => 2, 'core' => 1, 'legs' => 1],
        );

        $this->assertNotEmpty($results);

        foreach ($results as $r) {
            $fingerDays = $this->fingerDays($r['sessions'], [5]);

            // No two finger days adjacent (cyclic).
            foreach ($fingerDays as $d) {
                $this->assertNotContains(($d + 1) % 7, $fingerDays, 'consecutive finger days found');
            }

            // Finger sessions (grip + climbing) capped at 3.
            $fingerSessions = 1 + count(array_filter($r['sessions'], fn ($p) => $p['focus'] === 'grip'));
            $this->assertLessThanOrEqual(ScheduleGenerator::MAX_FINGER_SESSIONS, $fingerSessions);

            // Never flags a hard issue on its own output.
            $this->assertEmpty($this->gen->issues($r['sessions'], [5])['hard']);
        }
    }

    public function test_results_are_sorted_best_first(): void
    {
        $results = $this->gen->generate([0, 1, 2, 3, 4, 5, 6], [], ['grip' => 1, 'back' => 1, 'legs' => 1, 'core' => 1]);

        $scores = array_column($results, 'score');
        $sorted = $scores;
        sort($sorted);
        $this->assertSame($sorted, $scores);
    }

    public function test_infeasible_finger_load_yields_no_arrangement(): void
    {
        // 3 climbing days already hits the finger cap, so any grip session is impossible.
        $results = $this->gen->generate(
            trainingDays: [0, 2, 4],
            climbingDays: [1, 3, 5],
            frequencies: ['grip' => 2],
        );

        $this->assertEmpty($results);
    }

    public function test_issues_flags_grip_and_heavy_pull_on_the_same_day_as_soft(): void
    {
        $placements = [
            ['focus' => 'grip', 'day' => 0],
            ['focus' => 'back', 'day' => 0],
        ];

        $issues = $this->gen->issues($placements, []);
        $this->assertEmpty($issues['hard']);
        $this->assertNotEmpty($issues['soft']);
    }

    public function test_issues_flags_consecutive_finger_days_as_hard(): void
    {
        $placements = [['focus' => 'grip', 'day' => 0]];
        // climbing Tue (day 1) → grip Mon (day 0) is consecutive.
        $issues = $this->gen->issues($placements, [1]);

        $this->assertNotEmpty($issues['hard']);
    }

    public function test_low_interference_pairing_scores_better_than_a_clash(): void
    {
        // grip+legs same day (good) should beat grip+back same day (soft clash).
        $good = $this->gen->issues([['focus' => 'grip', 'day' => 0], ['focus' => 'legs', 'day' => 0]], []);
        $bad = $this->gen->issues([['focus' => 'grip', 'day' => 0], ['focus' => 'back', 'day' => 0]], []);

        $this->assertEmpty($good['soft']);
        $this->assertNotEmpty($bad['soft']);
    }
}
