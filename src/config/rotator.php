<?php

return [
    /**
     * Any class in the namespaces mentioned here will be rotated when
     * they are a Model or hen they implement RotatesEncryptedValues
     *
     * By default, only classes with the exact namespace are being processed.
     * Append an asterisk to process classes recursively.
     */
    'namespaces' => [
        'App*'
    ],

    /**
     * Place here any FQCN of models or classes that implement RotatesEncryptedValues
     */
    'class_paths' => [
    ],

    'chunk_size' => env('ROTATOR_CHUNK_SIZE', 50),
    'job_limit' => env('ROTATOR_JOB_LIMIT', 100),

    'queue' => env('ROTATOR_QUEUE', 'default'),
    'connection' => env('ROTATOR_QUEUE_CONNECTION', env('QUEUE_CONNECTION')),

    'keep_previous_key_count' => env('ROTATOR_KEEP_KEY_COUNT', 2),
];
