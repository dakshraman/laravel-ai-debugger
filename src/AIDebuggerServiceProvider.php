<?php

namespace Dakshraman\AIDebugger;

use Illuminate\Support\ServiceProvider;
use Dakshraman\AIDebugger\Services\DebugAnalyzer;
use Dakshraman\AIDebugger\Console\AnalyzeCommand;

class AIDebuggerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/ai-debugger.php',
            'ai-debugger'
        );

        $this->app->singleton('ai-debugger', function () {
            return new DebugAnalyzer();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/ai-debugger.php' => config_path('ai-debugger.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                AnalyzeCommand::class,
            ]);
        }
    }
}
