<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('schedules', 'last_status')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table
                ->timestamp('last_run_at')
                ->nullable();
            $table
                ->timestamp('last_finished_at')
                ->nullable();
            $table
                ->string('last_status')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn(['last_run_at', 'last_finished_at', 'last_status']);
        });
    }
};
