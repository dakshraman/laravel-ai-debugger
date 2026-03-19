<?php

namespace Dakshraman\AIDebugger\Tests;

use Dakshraman\AIDebugger\Services\DebugAnalyzer;
use Dakshraman\AIDebugger\AI\AIInterface;

class DebugAnalyzerTest extends TestCase
{
    public function test_analyze_returns_decoded_json_when_driver_returns_valid_json(): void
    {
        $mockDriver = new class implements AIInterface {
            public function analyze(string $input): string
            {
                return json_encode([
                    'root_cause' => 'Missing variable',
                    'fix'        => 'Define the variable before use',
                    'steps'      => ['Step 1', 'Step 2'],
                ]);
            }
        };

        $analyzer = new DebugAnalyzer($mockDriver);
        $result   = $analyzer->analyze('Undefined variable $foo');

        $this->assertArrayHasKey('root_cause', $result);
        $this->assertArrayHasKey('fix', $result);
        $this->assertArrayHasKey('steps', $result);
        $this->assertEquals('Missing variable', $result['root_cause']);
    }

    public function test_analyze_falls_back_to_raw_on_non_json_response(): void
    {
        $mockDriver = new class implements AIInterface {
            public function analyze(string $input): string
            {
                return 'Unable to process at this time.';
            }
        };

        $analyzer = new DebugAnalyzer($mockDriver);
        $result   = $analyzer->analyze('Some error trace');

        $this->assertArrayHasKey('raw', $result);
        $this->assertEquals('Unable to process at this time.', $result['raw']);
    }
}
