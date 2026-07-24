<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TrainController;
use App\Livewire\ScheduleBuilder;
use App\Livewire\WorkoutRunner;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [TrainController::class, 'today'])->name('dashboard');
    Route::get('/history', [TrainController::class, 'history'])->name('history');
    Route::get('/schedule', ScheduleBuilder::class)->name('schedule');
    Route::get('/run/{workout}', WorkoutRunner::class)->name('run');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
