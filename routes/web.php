<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainController;
use App\Livewire\BaselineTest;
use App\Livewire\ScheduleBuilder;
use App\Livewire\WorkoutRunner;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TrainController::class, 'today'])->name('dashboard');
    Route::get('/history', [TrainController::class, 'history'])->name('history');
    Route::get('/progress', [TrainController::class, 'progress'])->name('progress');
    Route::get('/schedule', ScheduleBuilder::class)->name('schedule');
    Route::get('/tests/{key}', BaselineTest::class)->name('tests.run');
    Route::get('/run/{workout}', WorkoutRunner::class)->name('run');
    Route::post('/api/set-log', [\App\Http\Controllers\SetLogController::class, 'store'])->name('api.set-log');

    Route::get('/api/push/key', [\App\Http\Controllers\PushController::class, 'key'])->name('api.push.key');
    Route::post('/api/push/subscribe', [\App\Http\Controllers\PushController::class, 'subscribe'])->name('api.push.subscribe');
    Route::delete('/api/push/subscribe', [\App\Http\Controllers\PushController::class, 'unsubscribe'])->name('api.push.unsubscribe');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
