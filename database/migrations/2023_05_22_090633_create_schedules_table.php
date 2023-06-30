<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id');
            $table->json('args')->nullable();
            $table->string('time_method');
            $table->json('time_params')->nullable();
            $table->boolean('without_overlapping')->default(false);
            $table->integer('without_overlapping_time')->default(1440);
            $table->boolean('run_in_background')->default(false);
            $table->boolean('run')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
