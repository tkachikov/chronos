<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('command_runs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id');
            $table->unsignedBigInteger('schedule_id')->nullable();
            $table->string('telescope_id')->nullable();
            $table->unsignedTinyInteger('state')->nullable();
            $table->string('memory')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('command_runs');
    }
};
