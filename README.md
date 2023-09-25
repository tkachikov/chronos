## Laravel pulse

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](https://opensource.org/licenses/MIT)

This package for setting commands in schedule.

## Installation

Require this package with composer using the following command
```shell
composer require tkachikov/laravel-pulse
```

Run Laravel pulse command for install:
```shell
php artisan pulse:install
```

Added Laravel pulse scheduler in `app/Console/Kernel.php`:
```php
// ...
use Tkachikov\LaravelPulse\Services\ScheduleService;

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

For authorization in production set statements in `app/Providers/LaravelPulseServiceProvider`, example:

```php
// ...
class LaravelPulseServiceProvider extends LaravelPulseApplicationServiceProvider
{
    // ...
    protected function gate(): void
    {
        Gate::define('viewPulse', function ($user) {
            return $user->hasRole('admin');
        });
    }
}
```

## Usage

Visit route `/route`, example: [localhost:8000/pulse](http://localhost:8000/pulse)

### For testing

Open `pulse:test` command:
![Open test](images/open_test.png)

Run `pulse:test` command:
![Run test](images/run_test.png)

### Run attributes

If you need off run command from Laravel Pulse dashboard (`notRunInManual`) or schedules (`notRunInSchedule`) set attributes:<br>
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

For logging command messages and set status added trait `PulseRunnerTrait`:
```php
// ...
class TestCommand extends Command
{
    use PulseRunnerTrait;
    // ...
}
```

### Create schedules

Open your command and set params for it in `Create schedule` and save.
![Create schedule](images/create_schedule.png)

For off command click button edit, check to off `Run` and save:
![Off schedule](images/off_schedule.png)

### Statistics

For calculate statistics run commands you must create schedule for `pulse:update-metrics`

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
