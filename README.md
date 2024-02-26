## Chronos

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

This package for setting commands in schedule.

## Installation

Require this package with composer using the following command
```shell
composer require tkachikov/chronos
```

Run Chronos command for install:
```shell
php artisan chronos:install
```

Added Chronos scheduler in `app/Console/Kernel.php`:
```php
// ...
use Tkachikov\Chronos\Services\ScheduleService;

// ...
class Kernel extends ConsoleKernel
{
    // ...
    protected function schedule(Schedule $schedule): void
    {
        app(ScheduleService::class)->schedule($schedule);
    }
    // ...
}
```

## Authorization

In defaults pages open for all users and also without auth middleware.

For open setting pages for authenticated users need uncommented 'auth' middleware in config `chronos.php`:
```php
return [
    'domain' => env('CHRONOS_DOMAIN'),

    'middlewares' => [
        'web',
        'auth',
        // 'Tkachikov\Chronos\Http\Middleware\Authorize',
    ],
];
```


For authorization in production uncommented Chronos auth in config `chronos.php` and set statements in `app/Providers/ChronosServiceProvider`:
```php
return [
    'domain' => env('CHRONOS_DOMAIN'),

    'middlewares' => [
        'web',
        'auth',
        'Tkachikov\Chronos\Http\Middleware\Authorize',
    ],
];
```
```php
// ...
class ChronosServiceProvider extends ChronosApplicationServiceProvider
{
    // ...
    protected function gate(): void
    {
        Gate::define('viewChronos', function ($user) {
            return $user->hasRole('admin');
        });
    }
}
```

## Usage

Visit route `/chronos`, example: [localhost:8000/chronos](http://localhost:8000/chronos)

### For testing

Open `chronos:test` command:
![Open test](images/open_test.png)

Run `chronos:test` command:
![Run test](images/run_test.png)

### Run attributes

If you need off run command from Chronos dashboard (`notRunInManual`) or schedules (`notRunInSchedule`) set attributes:<br>
For example all off:
```php
// ...
#[notRunInManual]
#[notRunInSchedule]
class TestCommand extends Command
{
    // ...
}
```

### Logging and states

For logging command messages and set status added trait `ChronosRunnerTrait`:
```php
// ...
class TestCommand extends Command
{
    use ChronosRunnerTrait;
    // ...
}
```

### Create schedules

Open your command and set params for it in `Create schedule` and save.
![Create schedule](images/create_schedule.png)

For off command click button edit, check to off `Run` and save:
![Off schedule](images/off_schedule.png)

### Statistics

For calculate statistics run commands you must create schedule for `chronos:update-metrics`

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
