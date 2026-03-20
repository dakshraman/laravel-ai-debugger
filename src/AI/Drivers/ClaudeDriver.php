<?php

namespace Dakshraman\AIDebugger\AI\Drivers;

class ClaudeDriver extends BaseDriver
{
    protected function executable(): string
    {
        return 'claude';
    }
}
