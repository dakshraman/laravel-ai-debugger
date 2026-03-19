<?php

namespace Dakshraman\AIDebugger\Services;

use Dakshraman\AIDebugger\AI\AIDriverManager;
use Dakshraman\AIDebugger\AI\AIInterface;

class DebugAnalyzer
{
    public function __construct(private readonly ?AIInterface $driver = null) {}

    public function analyze(string $trace): array
    {
        $driver = $this->driver ?? AIDriverManager::resolve();

        $raw = $driver->analyze($trace);

        return $this->normalize($raw);
    }

    protected function normalize(string $response): array
    {
        $decoded = json_decode($response, true);

        return $decoded ?? ['raw' => $response];
    }
}
