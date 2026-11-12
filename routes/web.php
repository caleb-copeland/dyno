<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainController;
use App\Livewire\BaselineTest;
use App\Livewire\ScheduleBuilder;
use App\Livewire\WorkoutRunner;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [TrainController::class, 'today'])->name('dashboard');
    Route::get('/history', [TrainController::class, 'history'])->name('history');
    Route::get('/progress', [TrainController::class, 'progress'])->name('progress');
    Route::get('/schedule', ScheduleBuilder::class)->name('schedule');
    Route::get('/tests/{key}', BaselineTest::class)->name('tests.run');
    Route::get('/run/{workout}', WorkoutRunner::class)->name('run');

    // Generous ceiling: an offline-queue flush replays every queued set toggle at once.
    Route::middleware('throttle:120,1')->group(function () {
        Route::post('/api/set-log', [\App\Http\Controllers\SetLogController::class, 'store'])->name('api.set-log');

        Route::get('/api/push/key', [\App\Http\Controllers\PushController::class, 'key'])->name('api.push.key');
        Route::post('/api/push/subscribe', [\App\Http\Controllers\PushController::class, 'subscribe'])->name('api.push.subscribe');
        Route::delete('/api/push/subscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('api.push.unsubscribe');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

// Local-only previews: branded emails (reset, invite) and the post-reset toast (log out first).
if (app()->isLocal()) {
    Route::get('/dev/mail/reset-password', fn () => (new \Illuminate\Auth\Notifications\ResetPassword('preview-token'))
        ->toMail(\App\Models\User::firstOrFail()));

    Route::get('/dev/mail/invite', fn () => (new \App\Notifications\InviteNotification(
        url: route('register', ['token' => 'preview-token']), expiresAt: now()->addDays(14), invitedBy: 'Caleb',
    ))->toMail(new \Illuminate\Notifications\AnonymousNotifiable));

    // Toast preview: what a member sees after a successful password reset.
    Route::get('/dev/toast', fn () => redirect()->route('login')->with('status', __('passwords.reset')));
}
