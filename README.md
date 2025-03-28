<p align="center">
    <img src="https://github.com/tkachikov/chronos/raw/master/images/logo.png" alt="Logo">
</p>

<p align="center">
  <a href="https://packagist.org/packages/tkachikov/chronos"><img src="https://img.shields.io/packagist/v/tkachikov/chronos.svg" alt="Packagist Version"></a>
  <a href="https://packagist.org/packages/tkachikov/chronos"><img src="https://img.shields.io/packagist/php-v/tkachikov/chronos.svg" alt="PHP from Packagist"></a>
  <img src="https://img.shields.io/badge/Laravel-12.x-blue" alt="Laravel 12.x">
  <img src="https://img.shields.io/badge/Laravel-11.x-blue" alt="Laravel 11.x">
  <img src="https://img.shields.io/badge/Laravel-10.x-blue" alt="Laravel 10.x">
  <img src="https://img.shields.io/badge/Laravel-9.x-blue" alt="Laravel 9.x">
  <a href="https://opensource.org/licenses/MIT"><img src="https://img.shields.io/badge/License-MIT-blue.svg" alt="License: MIT"></a>
</p>

<p align="center">
  <a href="https://github.com/tkachikov/chronos/stargazers"><img src="https://img.shields.io/github/stars/tkachikov/chronos.svg?style=social" alt="GitHub stars"></a>
  <a href="https://github.com/tkachikov/chronos/issues"><img src="https://img.shields.io/github/issues/tkachikov/chronos.svg" alt="GitHub issues"></a>
  <a href="https://github.com/tkachikov/chronos/commits/main"><img src="https://img.shields.io/github/last-commit/tkachikov/chronos.svg" alt="GitHub last commit"></a>
</p>

This package for setting commands in schedule.

## Installation

Require this package with composer using the following command
```shell
composer require tkachikov/chronos
```

Run Chronos command for install:
```shell
php artisan chronos:install --migrate
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
