<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LTI 1.3 Tool Configuration
    |--------------------------------------------------------------------------
    |
    | These settings configure your application as an LTI 1.3 Tool Provider.
    |
    */

    // Key ID for JWKS (should be unique)
    'key_id' => env('LTI_KEY_ID', 'motionbase-lti-key'),

    // Path to RSA private key for signing JWTs
    'private_key_path' => storage_path('lti/private.pem'),

    // Path to RSA public key for JWKS endpoint
    'public_key_path' => storage_path('lti/public.pem'),

    // Session duration in hours
    'session_duration' => env('LTI_SESSION_DURATION', 8),

    // Platform JWKS cache duration in minutes
    'jwks_cache_duration' => env('LTI_JWKS_CACHE_DURATION', 60),
];
