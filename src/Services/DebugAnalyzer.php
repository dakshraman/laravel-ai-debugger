<?php

namespace Dakshraman\AIDebugger\Services;

use Dakshraman\AIDebugger\AI\AIDriverManager;

class DebugAnalyzer
{
    public function analyze(string $trace): array
    {
        $driver = AIDriverManager::resolve();

        $raw = $driver->analyze($trace);

        return $this->normalize($raw);
    }

    protected function normalize(string $response): array
    {
        $decoded = json_decode($response, true);

        return $decoded ?? ['raw' => $response];
    }
}
