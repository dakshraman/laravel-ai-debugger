<?php

namespace Dakshraman\AIDebugger\AI;

use Dakshraman\AIDebugger\AI\Drivers\ClaudeDriver;
use Dakshraman\AIDebugger\AI\Drivers\CodexDriver;
use Dakshraman\AIDebugger\AI\Drivers\CopilotDriver;
use Dakshraman\AIDebugger\AI\Drivers\GeminiDriver;

class AIDriverManager
{
    public static function resolve(): AIInterface
    {
        return match (config('ai-debugger.driver')) {
            'codex'   => new CodexDriver(),
            'copilot' => new CopilotDriver(),
            'gemini'  => new GeminiDriver(),
            default   => new ClaudeDriver(),
        };
    }
}
