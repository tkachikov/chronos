<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tkachikov\Chronos\Enums\DatabaseEnum;

class DatabaseHelper
{
    public function hasConnect(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            //
        }

        return false;
    }

    public function hasTable(Model|string $model): bool
    {
        return $this->hasConnect()
            && Schema::hasTable($this->getTable($model));
    }

    public function getTable(Model|string $model): string
    {
        return $this->getObject($model)->getTable();
    }

    public function getObject(Model|string $model): Model
    {
        return is_string($model)
            ? new $model()
            : $model;
    }

    public function getTimeDiffInSeconds(string $start, string $end): string
    {
        return match ($this->getDriverEnum()) {
            DatabaseEnum::SQLITE => "strftime('%s', $end) - strftime('%s', $start)",
            DatabaseEnum::MYSQL => "timestampdiff(second, $start, $end)",
            DatabaseEnum::PGSQL => "extract(epoch from ($end - $start))",
        };
    }

    public function getConcat(...$args): string
    {
        return match($this->getDriverEnum()) {
            DatabaseEnum::MYSQL, DatabaseEnum::PGSQL => sprintf('concat(%s)', $this->prepareArgsConcat($args)),
            DatabaseEnum::SQLITE => $this->prepareArgsConcat($args, ' || '),
        };
    }

    public function prepareArgsConcat(array $args, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($arg) {
            return match(gettype($arg)) {
                'string' => "'$arg'",
                'object' => !($arg instanceof Expression) ?: $arg->getValue(DB::connection()->getQueryGrammar()),
                default => $arg,
            };
        }, $args));
    }

    public function getDefault(): string
    {
        return config('database.default');
    }

    public function getDriver(): string
    {
        return config("database.connections.{$this->getDefault()}.driver");
    }

    public function getDriverEnum(): ?DatabaseEnum
    {
        return DatabaseEnum::tryFrom($this->getDriver());
    }
}
