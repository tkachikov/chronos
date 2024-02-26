<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tkachikov\Chronos\Http\Controllers\ChronosController;

Route::controller(ChronosController::class)
    ->name('chronos.')
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
