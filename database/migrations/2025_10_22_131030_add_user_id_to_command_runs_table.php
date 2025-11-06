<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('command_runs', function (Blueprint $table) {
            $table
                ->unsignedBigInteger('pid')
                ->nullable();
            $table
                ->nullableMorphs('user');
            $table
                ->json('args')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('command_runs', function (Blueprint $table) {
            $table->dropColumn('pid');
            $table->dropMorphs('user');
            $table->dropColumn('args');
        });
    }
};
