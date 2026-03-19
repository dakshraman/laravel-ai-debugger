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

        $this->app->singleton(DebugAnalyzer::class, function () {
            return new DebugAnalyzer();
        });

        // Allow both the string alias ('ai-debugger') used by the Facade and
        // the class name (DebugAnalyzer::class) used by command type-hint
        // injection to resolve to the same singleton.
        $this->app->alias(DebugAnalyzer::class, 'ai-debugger');
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
