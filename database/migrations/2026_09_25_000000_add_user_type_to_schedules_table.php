<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasColumn('schedules', 'user_type')) {
            return;
        }

        Schema::table('schedules', function (Blueprint $table) {
            $table
                ->string('user_type')
                ->nullable()
                ->after('run');
            $table->index(['user_type', 'user_id']);
        });

        $userType = $this->getDefaultUserType();

        if ($userType) {
            DB::table('schedules')
                ->whereNotNull('user_id')
                ->update(['user_type' => $userType]);
        }
    }

    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'user_id']);
            $table->dropColumn('user_type');
        });
    }

    private function getDefaultUserType(): ?string
    {
        $guard = config('auth.defaults.guard');
        $provider = config("auth.guards.$guard.provider");
        $model = config("auth.providers.$provider.model");

        if (
            !is_string($model)
            || !class_exists($model)
            || !is_subclass_of($model, Model::class)
        ) {
            return null;
        }

        return (new $model())->getMorphClass();
    }
};
