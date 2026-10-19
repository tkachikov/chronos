<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('schedules', 'skipped_count')) {
            Schema::table('schedules', function (Blueprint $table) {
                $table
                    ->timestamp('last_skipped_at')
                    ->nullable();
                $table
                    ->unsignedInteger('skipped_count')
                    ->default(0);
            });
        }

        if (!Schema::hasColumn('command_runs', 'host')) {
            Schema::table('command_runs', function (Blueprint $table) {
                $table
                    ->string('host')
                    ->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['last_skipped_at', 'skipped_count']);
        });

        Schema::table('command_runs', function (Blueprint $table) {
            $table->dropColumn('host');
        });
    }
};
