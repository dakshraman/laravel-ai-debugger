<?php

namespace Dakshraman\AIDebugger\Tests;

use Dakshraman\AIDebugger\Services\DebugAnalyzer;
use Dakshraman\AIDebugger\Facades\AIDebugger;

class ServiceProviderTest extends TestCase
{
    public function test_ai_debugger_binding_resolves_to_debug_analyzer(): void
    {
        $analyzer = $this->app->make('ai-debugger');

        $this->assertInstanceOf(DebugAnalyzer::class, $analyzer);
    }

    public function test_debug_analyzer_class_binding_resolves_to_same_singleton(): void
    {
        $viaAlias = $this->app->make('ai-debugger');
        $viaClass = $this->app->make(DebugAnalyzer::class);

        $this->assertSame($viaAlias, $viaClass);
    }

    public function test_config_values_are_merged(): void
    {
        $this->assertNotNull(config('ai-debugger.driver'));
        $this->assertNotNull(config('ai-debugger.log_path'));
    }

    public function test_default_driver_is_claude(): void
    {
        $this->assertEquals('claude', config('ai-debugger.driver'));
    }

    public function test_debug_analyze_command_is_registered(): void
    {
        $this->artisan('debug:analyze --help')
            ->assertExitCode(0);
    }
}
