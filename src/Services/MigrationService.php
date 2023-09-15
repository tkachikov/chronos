<?php

declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class MigrationService
{
    public const COMMANDS = 'commands';

    public const COMMAND_RUNS = 'command_runs';

    public const COMMAND_LOGS = 'command_logs';

    public const COMMAND_METRICS = 'command_metrics';

    public const SCHEDULES = 'schedules';

    /**
     * @return void
     */
    public function createAll(): void
    {
        $this->createCommands();
        $this->createCommandRuns();
        $this->createCommandLogs();
        $this->createCommandMetrics();
        $this->createSchedules();
    }

    /**
     * @return void
     */
    public function removeAll(): void
    {
        $this->removeCommands();
        $this->removeCommandRuns();
        $this->removeCommandLogs();
        $this->removeCommandMetrics();
        $this->removeSchedules();
    }

    /**
     * @return void
     */
    public function createCommands(): void
    {
        Schema::create(self::COMMANDS, function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function createCommandRuns(): void
    {
        Schema::create(self::COMMAND_RUNS, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id')->index();
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('telescope_id')->nullable();
            $table->unsignedTinyInteger('state')->nullable();
            $table->string('memory')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function createCommandLogs(): void
    {
        Schema::create(self::COMMAND_LOGS, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_run_id')->index();
            $table->string('type');
            $table->text('message');
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function createCommandMetrics(): void
    {
        Schema::create(self::COMMAND_METRICS, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id');
            foreach (['time', 'memory'] as $type) {
                foreach (['avg', 'min', 'max'] as $key) {
                    $table->string($type.'_'.$key);
                }
            }
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function createSchedules(): void
    {
        Schema::create(self::SCHEDULES, function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id');
            $table->json('args')->nullable();
            $table->string('time_method');
            $table->json('time_params')->nullable();
            $table->boolean('without_overlapping')->default(false);
            $table->integer('without_overlapping_time')->default(1440);
            $table->boolean('run_in_background')->default(false);
            $table->boolean('run')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * @return void
     */
    public function removeCommands(): void
    {
        $this->drop(self::COMMANDS);
    }

    /**
     * @return void
     */
    public function removeCommandRuns(): void
    {
        $this->drop(self::COMMAND_RUNS);
    }

    /**
     * @return void
     */
    public function removeCommandLogs(): void
    {
        $this->drop(self::COMMAND_LOGS);
    }

    /**
     * @return void
     */
    public function removeCommandMetrics(): void
    {
        $this->drop(self::COMMAND_METRICS);
    }

    /**
     * @return void
     */
    public function removeSchedules(): void
    {
        $this->drop(self::SCHEDULES);
    }

    /**
     * @param string $table
     *
     * @return void
     */
    private function drop(string $table): void
    {
        Schema::dropIfExists($table);
    }
}