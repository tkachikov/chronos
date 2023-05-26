<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Tkachikov\LaravelCommands\Http\Controllers\CommandController;

Route::prefix('commands')
    ->name('commands.')
    ->controller(CommandController::class)
    ->group(function () {
        Route::get('', 'index')->name('index');
        Route::prefix('{command}')->group(function () {
            Route::get('', 'edit')->name('edit');
            Route::post('', 'update')->name('update');
            Route::post('run', 'run')->name('run');
            Route::prefix('schedules')->name('schedules.')->group(function () {
                Route::delete('{schedule}', 'destroy')->name('destroy');
            });
        });
    });
