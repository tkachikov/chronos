<?php
declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;

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
}
