<?php

/*
 * Public website details (home / support page). Set these in Render's
 * Environment, not in code: the repository is public.
 */
return [
    'support_email' => env('SUPPORT_EMAIL'),
    'support_phone' => env('SUPPORT_PHONE'),
    // store links: the download buttons appear once these are set
    'app_store_url' => env('APP_STORE_URL'),
    'play_store_url' => env('PLAY_STORE_URL'),
];
