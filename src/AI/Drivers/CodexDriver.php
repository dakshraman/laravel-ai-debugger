<?php

namespace Dakshraman\AIDebugger\AI\Drivers;

class CodexDriver extends BaseDriver
{
    protected function executable(): string
    {
        return 'codex';
    }
}
