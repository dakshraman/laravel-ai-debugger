<?php

namespace Dakshraman\AIDebugger\Facades;

use Illuminate\Support\Facades\Facade;

class AIDebugger extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'ai-debugger';
    }
}
