<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Tkachikov\Chronos\Services\MigrationService;

return new class extends Migration
{
    public function up(): void
    {
        app(MigrationService::class)->createCommandMetrics();
    }

    public function down(): void
    {
        app(MigrationService::class)->removeCommandMetrics();
    }
};
