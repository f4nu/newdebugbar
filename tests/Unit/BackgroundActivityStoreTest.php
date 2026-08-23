<?php

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use NewDebugBar\Storage\BackgroundActivityStore;

it('keeps bounded dispatch and worker attempt correlation facts', function (): void {
    $files = new Filesystem;
    $path = sys_get_temp_dir().'/newdebugbar-background-'.bin2hex(random_bytes(8));
    $store = new BackgroundActivityStore($files, $path, maxActivities: 2, maxAgeMinutes: 60);

    try {
        $origin = (string) Str::uuid();
        $first = $store->recordDispatch([
            'origin_profile_id' => $origin,
            'job_id' => 41,
            'job' => 'App\\Mail\\JourneyReviewReady',
            'connection' => 'database',
            'queue' => 'mail',
            'delay_seconds' => 30,
            'communication_type' => 'mail',
            'communication_class' => 'App\\Mail\\JourneyReviewReady',
            'channels' => ['mail'],
            'recipient_count' => 1,
        ]);

        expect($first)
            ->status->toBe('delayed')
            ->origin_profile_id->toBe($origin)
            ->communication_class->toBe('App\\Mail\\JourneyReviewReady');

        $processing = $store->markProcessing('database', 'mail', 41, 1);
        $failedProfile = (string) Str::uuid();
        $waiting = $store->recordOutcome($first['key'], 'waiting', $failedProfile, 1, RuntimeException::class);
        $sentProfile = (string) Str::uuid();
        $sent = $store->recordOutcome($first['key'], 'sent', $sentProfile, 2);

        expect($processing)->status->toBe('processing')
            ->attempt->toBe(1)
            ->and($waiting)->status->toBe('waiting')
            ->worker_profile_id->toBe($failedProfile)
            ->and($sent)->status->toBe('sent')
            ->attempt->toBe(2)
            ->worker_profile_id->toBe($sentProfile)
            ->and(array_column($sent['attempts'], 'status'))->toBe(['failed', 'sent']);

        foreach ([42, 43] as $jobId) {
            $store->recordDispatch([
                'origin_profile_id' => $origin,
                'job_id' => $jobId,
                'job' => 'App\\Jobs\\BoundedJob',
                'connection' => 'database',
                'queue' => 'default',
            ]);
        }

        expect($files->files($path))->toHaveCount(2);
    } finally {
        $files->deleteDirectory($path);
    }
});
