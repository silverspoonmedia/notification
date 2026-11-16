<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notification bell delivery mode
    |--------------------------------------------------------------------------
    |
    | Controls whether the navbar bell uses HTTP polling or websocket push.
    | Accepted values: polling | realtime
    |
    | Realtime also requires a valid broadcast connection (Pusher / Reverb).
    | Misconfigured realtime falls back to polling so the bell keeps working.
    |
    */

    'delivery' => 'polling',

    /*
    |--------------------------------------------------------------------------
    | Polling interval (seconds)
    |--------------------------------------------------------------------------
    |
    | How often the bell refetches the unread badge when delivery mode is polling
    | (including realtime fallback when Pusher is misconfigured). Minimum: 1.
    |
    */

    'polling_interval' => 60,

];
