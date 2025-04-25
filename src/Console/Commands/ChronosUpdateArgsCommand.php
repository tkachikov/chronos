<?php

declare(strict_types=1);

namespace Tkachikov\Chronos\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Tkachikov\Chronos\Models\Schedule;

final class ChronosUpdateArgsCommand extends Command
{
    protected $signature = 'chronos:update-args';

    protected $description = 'Update args for fix sorting in MySQL';

    public function handle(): void
    {
        $table = (new Schedule())->getTable();

        DB::table($table)
            ->whereNotNull('args')
            ->eachById(function (object $schedule) {
                if (!$schedule->args) {
                    return;
                }

                $args = json_decode($schedule->args, true);

                if (is_string(array_keys($args)[0])) {
                    $newArgs = [];

                    foreach (json_decode($schedule->args, true) as $key => $value) {
                        $newArgs[] = [
                            'key' => $key,
                            'value' => $value,
                        ];
                    }

                    Schedule::query()
                        ->where('id', $schedule->id)
                        ->update(['args' => json_encode($newArgs)]);
                }
            });
    }
}