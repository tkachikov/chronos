<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Expression;
use Tkachikov\LaravelPulse\Enums\DatabaseEnum;
use Illuminate\Database\Query\Grammars\Grammar;

class DatabaseHelper
{
    /**
     * @return bool
     */
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

    /**
     * @param Model|string $model
     *
     * @return bool
     */
    public function hasTable(Model|string $model): bool
    {
        return Schema::hasTable($this->getTable($model));
    }

    /**
     * @param Model|string $model
     *
     * @return string
     */
    public function getTable(Model|string $model): string
    {
        return $this->getObject($model)->getTable();
    }

    /**
     * @param Model|string $model
     *
     * @return Model
     */
    public function getObject(Model|string $model): Model
    {
        return is_string($model)
            ? new $model
            : $model;
    }

    /**
     * @param string $start
     * @param string $end
     *
     * @return string
     */
    public function getTimeDiffInSeconds(string $start, string $end): string
    {
        return match ($this->getDriverEnum()) {
            DatabaseEnum::SQLITE => "strftime('%s', $end) - strftime('%s', $start)",
            DatabaseEnum::MYSQL => "timestampdiff(second, $start, $end)",
            DatabaseEnum::PGSQL => "extract(epoch from ($end - $start))",
        };
    }

    /**
     * @param ...$args
     *
     * @return string
     */
    public function getConcat(...$args): string
    {
        return match($this->getDriverEnum()) {
            DatabaseEnum::MYSQL, DatabaseEnum::PGSQL => sprintf('concat(%s)', $this->prepareArgsConcat($args)),
            DatabaseEnum::SQLITE => $this->prepareArgsConcat($args, ' || '),
        };
    }

    /**
     * @param array  $args
     * @param string $separator
     *
     * @return string
     */
    public function prepareArgsConcat(array $args, string $separator = ', '): string
    {
        return implode($separator, array_map(function ($arg) {
            return match(gettype($arg)) {
                'string' => "'$arg'",
                'object' => !($arg instanceof Expression) ?: $arg->getValue(app(Grammar::class)),
                default => $arg,
            };
        }, $args));
    }

    /**
     * @return string
     */
    public function getDefault(): string
    {
        return config('database.default');
    }

    /**
     * @return string
     */
    public function getDriver(): string
    {
        return config("database.connections.{$this->getDefault()}.driver");
    }

    /**
     * @return DatabaseEnum|null
     */
    public function getDriverEnum(): ?DatabaseEnum
    {
        return DatabaseEnum::tryFrom($this->getDriver());
    }
}
