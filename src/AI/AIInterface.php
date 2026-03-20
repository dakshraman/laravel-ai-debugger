<?php

namespace Dakshraman\AIDebugger\AI;

interface AIInterface
{
    public function analyze(string $input): string;
}
