<?php

namespace App\Console\Commands;

use App\Models\Schedule;
use App\Services\WebPushSender;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Notifies users who have a session scheduled today (and a push subscription).
 * Run daily via the scheduler (routes/console.php).
 */
#[Signature('app:send-reminders')]
#[Description('Send push reminders to users training today')]
class SendSessionReminders extends Command
{
    public function handle(WebPushSender $sender): int
    {
        $todayNum = now()->dayOfWeekIso - 1; // 0=Mon…6=Sun

        $schedules = Schedule::where('active', true)
            ->with([
                'sessions' => fn ($q) => $q->where('day_of_week', $todayNum),
                'user.pushSubscriptions',
            ])
            ->get();

        $sent = 0;
        foreach ($schedules as $schedule) {
            if ($schedule->sessions->isEmpty()) {
                continue;
            }
            $subs = $schedule->user->pushSubscriptions;
            if ($subs->isEmpty()) {
                continue;
            }

            $areas = $schedule->sessions->map(fn ($s) => $s->focus_area->label())->unique()->join(', ');
            $payload = [
                'title' => 'Training today',
                'body' => "{$areas} — open Dyno to start your session.",
                'url' => route('dashboard'),
            ];

            foreach ($subs as $sub) {
                if ($sender->send($sub, $payload)) {
                    $sent++;
                }
            }
        }

        $this->info("Reminders sent: {$sent}");

        return self::SUCCESS;
    }
}
