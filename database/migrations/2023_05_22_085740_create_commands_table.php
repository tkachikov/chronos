<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('commands')) {
            return;
        }

        Schema::create('commands', function (Blueprint $table) {
            $table->id();
            $table->string('class');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commands');
    }
};
