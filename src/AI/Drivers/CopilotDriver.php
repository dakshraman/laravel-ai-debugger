<?php

namespace Dakshraman\AIDebugger\AI\Drivers;

class CopilotDriver extends BaseDriver
{
    protected function executable(): string
    {
        return 'gh';
    }

    protected function executableArgs(): array
    {
        return ['copilot', 'suggest', '-t', 'shell'];
    }
}
