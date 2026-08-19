<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Optional modules
    |--------------------------------------------------------------------------
    |
    | Keep the instance billing portal turned off until it is ready to be
    | offered. Its route and menu entries are only registered when enabled.
    |
    */
    'subscription_module_enabled' => env('SUBSCRIPTION_MODULE_ENABLED', false),
];
