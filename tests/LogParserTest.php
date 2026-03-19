<?php

namespace Dakshraman\AIDebugger\Tests;

use Dakshraman\AIDebugger\Helpers\LogParser;

class LogParserTest extends TestCase
{
    public function test_extracts_error_entries_from_log(): void
    {
        $log = <<<LOG
[2024-01-15 10:00:00] local.ERROR: Something went wrong {"exception":"[object] (RuntimeException..."}
[2024-01-15 10:01:00] local.INFO: Request completed
[2024-01-15 10:02:00] local.CRITICAL: Database connection failed
LOG;

        $errors = LogParser::extractErrors($log);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('ERROR', $errors[0]);
        $this->assertStringContainsString('CRITICAL', $errors[1]);
    }

    public function test_returns_empty_array_when_no_errors(): void
    {
        $log = <<<LOG
[2024-01-15 10:01:00] local.INFO: Request completed
[2024-01-15 10:02:00] local.DEBUG: Rendered view
LOG;

        $errors = LogParser::extractErrors($log);

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }

    public function test_returns_empty_array_on_empty_input(): void
    {
        $errors = LogParser::extractErrors('');

        $this->assertIsArray($errors);
        $this->assertEmpty($errors);
    }
}
