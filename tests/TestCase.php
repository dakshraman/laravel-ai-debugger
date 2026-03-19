<?php

namespace Dakshraman\AIDebugger\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Dakshraman\AIDebugger\AIDebuggerServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            AIDebuggerServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'AIDebugger' => \Dakshraman\AIDebugger\Facades\AIDebugger::class,
        ];
    }
}
