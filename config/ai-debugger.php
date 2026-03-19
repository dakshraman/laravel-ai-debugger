<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Driver
    |--------------------------------------------------------------------------
    |
    | The AI CLI driver to use for debugging analysis.
    | Supported: "claude", "gemini", "copilot", "codex"
    |
    */
    'driver' => env('AI_DEBUGGER_DRIVER', 'claude'),

    /*
    |--------------------------------------------------------------------------
    | Log Path
    |--------------------------------------------------------------------------
    |
    | Default log file path used by the debug:analyze command.
    |
    */
    'log_path' => env('AI_DEBUGGER_LOG_PATH', storage_path('logs/laravel.log')),
];
