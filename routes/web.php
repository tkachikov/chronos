<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tkachikov\LaravelPulse\Http\Controllers\CommandController;

Route::controller(CommandController::class)
    ->name('pulse.')
    ->group(function () {
        Route::get('', 'index')->name('main');
        Route::prefix('{command}')->group(function () {
            Route::get('', 'edit')->name('edit');
            Route::post('', 'update')->name('update');
            Route::post('run', 'run')->name('run');
            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::delete('{schedule}', 'destroy')->name('destroy');
            });
        });
    });
