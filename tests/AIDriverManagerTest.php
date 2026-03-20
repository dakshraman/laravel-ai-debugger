<?php

namespace Dakshraman\AIDebugger\Tests;

use Dakshraman\AIDebugger\AI\AIDriverManager;
use Dakshraman\AIDebugger\AI\Drivers\ClaudeDriver;
use Dakshraman\AIDebugger\AI\Drivers\CodexDriver;
use Dakshraman\AIDebugger\AI\Drivers\CopilotDriver;
use Dakshraman\AIDebugger\AI\Drivers\GeminiDriver;

class AIDriverManagerTest extends TestCase
{
    public function test_resolves_claude_driver_by_default(): void
    {
        config(['ai-debugger.driver' => 'claude']);

        $driver = AIDriverManager::resolve();

        $this->assertInstanceOf(ClaudeDriver::class, $driver);
    }

    public function test_resolves_gemini_driver(): void
    {
        config(['ai-debugger.driver' => 'gemini']);

        $driver = AIDriverManager::resolve();

        $this->assertInstanceOf(GeminiDriver::class, $driver);
    }

    public function test_resolves_codex_driver(): void
    {
        config(['ai-debugger.driver' => 'codex']);

        $driver = AIDriverManager::resolve();

        $this->assertInstanceOf(CodexDriver::class, $driver);
    }

    public function test_resolves_copilot_driver(): void
    {
        config(['ai-debugger.driver' => 'copilot']);

        $driver = AIDriverManager::resolve();

        $this->assertInstanceOf(CopilotDriver::class, $driver);
    }

    public function test_falls_back_to_claude_for_unknown_driver(): void
    {
        config(['ai-debugger.driver' => 'unknown']);

        $driver = AIDriverManager::resolve();

        $this->assertInstanceOf(ClaudeDriver::class, $driver);
    }
}
