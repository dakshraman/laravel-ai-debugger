<?php

namespace Dakshraman\AIDebugger\AI;

use Dakshraman\AIDebugger\AI\Drivers\ClaudeDriver;
use Dakshraman\AIDebugger\AI\Drivers\GeminiDriver;
use Dakshraman\AIDebugger\AI\Drivers\CopilotDriver;

class AIDriverManager
{
    public static function resolve(): AIInterface
    {
        return match (config('ai-debugger.driver')) {
            'gemini'  => new GeminiDriver(),
            'copilot' => new CopilotDriver(),
            default   => new ClaudeDriver(),
        };
    }
}
