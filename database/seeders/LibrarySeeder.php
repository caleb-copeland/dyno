<?php

namespace Database\Seeders;

use App\Enums\FocusArea;
use App\Enums\PrescriptionBasis;
use App\Enums\PrescriptionType;
use App\Models\Exercise;
use App\Models\Workout;
use Illuminate\Database\Seeder;

class LibrarySeeder extends Seeder
{
    public function run(): void
    {
        // --- Exercises (curated starter set, keyed by slug for wiring workouts) ---
        $ex = [];
        foreach ($this->exercises() as $slug => $data) {
            $ex[$slug] = Exercise::updateOrCreate(
                ['name' => $data['name']],
                $data,
            );
        }

        // --- A couple of curated workouts to exercise the runner ---
        $this->workout('Hangboard Strength — Max Hangs', FocusArea::Grip, 'advanced', 30, [
            [$ex['max_hang'], ['sets' => 5, 'prescription_basis' => PrescriptionBasis::PercentOfTest, 'percent_of_test' => 0.90,
                'interval_work_s' => 10, 'interval_rest_s' => 0, 'interval_reps' => 1, 'rest_s' => 180]],
            [$ex['repeaters'], ['sets' => 3, 'interval_work_s' => 7, 'interval_rest_s' => 3, 'interval_reps' => 6, 'rest_s' => 180]],
        ]);

        $this->workout('Pulling Power', FocusArea::Back, 'intermediate', 40, [
            [$ex['weighted_pullup'], ['sets' => 5, 'target_reps' => 5, 'prescription_basis' => PrescriptionBasis::PercentOfTest, 'percent_of_test' => 0.80, 'rest_s' => 180]],
            [$ex['pullups'], ['sets' => 3, 'target_reps' => 8, 'rest_s' => 120]],
            [$ex['bicep_curl'], ['sets' => 3, 'target_reps' => 12, 'rest_s' => 90]],
        ]);

        $this->workout('Antagonist & Core', FocusArea::Push, 'beginner', 25, [
            [$ex['pushups'], ['sets' => 3, 'target_reps' => 15, 'rest_s' => 60]],
            [$ex['shoulder_press'], ['sets' => 3, 'target_reps' => 10, 'rest_s' => 90]],
            [$ex['front_lever'], ['sets' => 4, 'target_duration_s' => 10, 'prescription_basis' => PrescriptionBasis::Rpe, 'rest_s' => 120]],
            [$ex['leg_raise'], ['sets' => 3, 'target_reps' => 12, 'rest_s' => 60]],
        ]);
    }

    /** @return array<string, array<string, mixed>> */
    private function exercises(): array
    {
        return [
            'max_hang' => ['name' => 'Max Hang — 20mm edge', 'focus_area' => FocusArea::Grip, 'prescription_type' => PrescriptionType::Interval, 'is_finger_intensive' => true, 'instructions' => 'Half-crimp or open hand on a 20mm edge, added weight. Warm up thoroughly first.'],
            'repeaters' => ['name' => 'Repeaters — 7:3', 'focus_area' => FocusArea::Grip, 'prescription_type' => PrescriptionType::Interval, 'is_finger_intensive' => true, 'instructions' => '7s hang, 3s rest, ×6 per set. Sub-maximal load.'],
            'weighted_pullup' => ['name' => 'Weighted Pull-up', 'focus_area' => FocusArea::Back, 'prescription_type' => PrescriptionType::Weight, 'is_finger_intensive' => false, 'instructions' => 'Full range, controlled. Jug or bar.'],
            'pullups' => ['name' => 'Pull-ups', 'focus_area' => FocusArea::Back, 'prescription_type' => PrescriptionType::Reps, 'is_finger_intensive' => false],
            'bicep_curl' => ['name' => 'Bicep Curl', 'focus_area' => FocusArea::Arms, 'prescription_type' => PrescriptionType::Weight, 'is_finger_intensive' => false],
            'front_lever' => ['name' => 'Front Lever Progression', 'focus_area' => FocusArea::Core, 'prescription_type' => PrescriptionType::Time, 'is_finger_intensive' => false, 'instructions' => 'Tuck → advanced tuck → one-leg → full, per your level.'],
            'leg_raise' => ['name' => 'Hanging Leg Raise', 'focus_area' => FocusArea::Core, 'prescription_type' => PrescriptionType::Reps, 'is_finger_intensive' => false],
            'pushups' => ['name' => 'Push-ups', 'focus_area' => FocusArea::Push, 'prescription_type' => PrescriptionType::Reps, 'is_finger_intensive' => false],
            'shoulder_press' => ['name' => 'Shoulder Press', 'focus_area' => FocusArea::Push, 'prescription_type' => PrescriptionType::Weight, 'is_finger_intensive' => false],
            'squat' => ['name' => 'Goblet Squat', 'focus_area' => FocusArea::Legs, 'prescription_type' => PrescriptionType::Weight, 'is_finger_intensive' => false],
        ];
    }

    /**
     * @param  array<int, array{0: Exercise, 1: array<string, mixed>}>  $items
     */
    private function workout(string $name, FocusArea $focus, string $level, int $minutes, array $items): void
    {
        $workout = Workout::updateOrCreate(
            ['name' => $name],
            ['focus_area' => $focus, 'level' => $level, 'estimated_minutes' => $minutes, 'is_published' => true],
        );

        $workout->workoutExercises()->delete();

        foreach ($items as $position => [$exercise, $pivot]) {
            $workout->workoutExercises()->create(array_merge([
                'exercise_id' => $exercise->id,
                'position' => $position,
            ], $pivot));
        }
    }
}
