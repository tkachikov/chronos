<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('scheduler_heartbeats')) {
            return;
        }

        Schema::create('scheduler_heartbeats', function (Blueprint $table) {
            $table->id();
            $table->string('host')->unique();
            $table->timestamp('last_run_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduler_heartbeats');
    }
};
