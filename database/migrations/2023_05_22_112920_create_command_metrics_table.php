<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('i_command_metrics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('command_id');
            foreach (['time', 'memory'] as $type) {
                foreach (['avg', 'min', 'max'] as $key) {
                    $table->string($type.'_'.$key);
                }
            }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('i_command_metrics');
    }
};
