<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('schedules', 'name')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table
                ->string('name')
                ->nullable()
                ->after('command_id');
        });
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('name');
        });
    }
};
