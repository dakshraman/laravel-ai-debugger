<?php

namespace Dakshraman\AIDebugger\AI\Drivers;

class GeminiDriver extends BaseDriver
{
    protected function executable(): string
    {
        return 'gemini';
    }
}
