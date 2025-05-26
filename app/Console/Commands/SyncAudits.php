<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class SyncAudits extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-audits';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->flatFormat();
    }

    public function nestedFormat():void
    {
        $logs = Activity::where('created_at', '>=', now()->subDay())->get();

        $ndjson = $logs->map(function ($log) {
            $props = $log->properties->toArray();

            return json_encode([
                                   'id' => $log->id,
                                   'model_type' => $log->subject_type,
                                   'model_id' => $log->subject_id,
                                   'event_type' => $log->event,
                                   'changes' => [
                                       'old' => $props['old'] ?? null,
                                       'new' => $props['attributes'] ?? null,
                                   ],
                                   'user_id' => $log->causer_id,
                                   'created_at' => $log->created_at->toISOString()
                               ]);
        })->implode("\n");

        Storage::disk('s3')->put('datalake/logs/activity_' . now()->timestamp . '.json', $ndjson);
    }
    public function flatFormat(): void
    {
        $logs = Activity::where('created_at', '>=', now()->subDay())->get();

        $lines = collect();

        foreach ($logs as $log) {
            $old = $log->properties['old'] ?? [];
            $new = $log->properties['attributes'] ?? [];

            foreach ($new as $key => $newValue) {
                $lines->push(json_encode([
                                             'id' => $log->id,
                                             'model_type' => $log->subject_type,
                                             'model_id' => $log->subject_id,
                                             'event_type' => $log->event,
                                             'field' => $key,
                                             'old_value' => $old[$key] ?? null,
                                             'new_value' => $newValue,
                                             'user_id' => $log->causer_id,
                                             'created_at' => $log->created_at->toISOString()
                                         ]));
            }
        }

        Storage::disk('s3')->put('datalake/logs/flat_' . now()->timestamp . '.json', $lines->implode("\n"));
    }
}
