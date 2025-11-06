<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tkachikov\Chronos\Http\Controllers\ChronosController;

Route::controller(ChronosController::class)
    ->name('chronos.')
    ->group(function () {
        Route::get('/', 'index')->name('main');
        Route::prefix('/{command}')->group(function () {
            Route::get('/', 'edit')->name('edit');
            Route::post('/', 'update')->name('update');
            Route::post('/run', 'run')->name('run');
            Route::prefix('/run-in-real-time')->name('runInRealTime.')->group(function () {
                Route::post('/', 'runInRealTime')->name('run');
                Route::get('/logs', 'getLogsForRunInRealTime')->name('logs');
                Route::post('/answer', 'setAnswerForRunning')->name('answer');
                Route::post('/sigterm', 'sigterm')->name('sigterm');
                Route::post('/sigkill', 'sigkill')->name('sigkill');
            });
            Route::prefix('/schedules')->name('schedules.')->group(function () {
                Route::delete('/{schedule}', 'destroy')->name('destroy');
            });
        });
    });
