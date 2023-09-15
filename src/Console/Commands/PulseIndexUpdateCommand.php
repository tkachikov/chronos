<?php

declare(strict_types=1);

namespace Tkachikov\LaravelPulse\Console\Commands;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class PulseIndexUpdateCommand extends Command
{
    protected $signature = 'pulse:update-indexes';

    protected $description = 'Update command_runs and command_logs';

    private array $columns = [
        'command_runs' => 'command_id',
        'command_logs' => 'command_run_id',
    ];

    /**
     * @throws Exception
     *
     * @return int
     */
    public function handle(): int
    {
        foreach ($this->columns as $table => $column) {
            if ($this->indexNotExists($table, $column)) {
                $this->createIndex($table, $column);
            }
        }

        return self::SUCCESS;
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @return void
     */
    private function createIndex(string $table, string $column): void
    {
        Schema::table($table, fn (Blueprint $table) => $table->index([$column]));
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @throws Exception
     *
     * @return bool
     */
    public function indexNotExists(string $table, string $column): bool
    {
        return !$this->indexExists($table, $column);
    }

    /**
     * @param string $table
     * @param string $column
     *
     * @throws Exception
     *
     * @return bool
     */
    private function indexExists(string $table, string $column): bool
    {
        return isset($this->getIndexes($table)["{$table}_{$column}_index"]);
    }

    /**
     * @param string $table
     *
     * @throws Exception
     *
     * @return array
     */
    private function getIndexes(string $table): array
    {
        return Schema::getConnection()
            ->getDoctrineSchemaManager()
            ->listTableIndexes($table);
    }
}
