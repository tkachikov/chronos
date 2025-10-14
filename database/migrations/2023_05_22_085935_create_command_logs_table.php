<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('command_logs')) {
            return;
        }

        Schema::create('command_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_run_id')->index();
            $table->string('type');
            $table->text('message');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_logs');
    }
};
