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

    public function test_extracts_alert_and_emergency_entries(): void
    {
        $log = <<<LOG
[2024-01-15 10:00:00] local.ALERT: Disk space critically low
[2024-01-15 10:01:00] local.EMERGENCY: System is unusable
[2024-01-15 10:02:00] local.INFO: Everything fine
LOG;

        $errors = LogParser::extractErrors($log);

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('ALERT', $errors[0]);
        $this->assertStringContainsString('EMERGENCY', $errors[1]);
    }

    public function test_extracts_multiline_stack_trace_entries(): void
    {
        $log = <<<'LOG'
[2024-01-15 10:00:00] local.ERROR: Unhandled exception
#0 /var/www/html/app/Http/Controllers/FooController.php(42): App\Services\FooService->bar()
#1 /var/www/html/vendor/laravel/framework/src/Illuminate/Routing/Controller.php(54): call_user_func_array()
[2024-01-15 10:01:00] local.INFO: Request completed
LOG;

        $errors = LogParser::extractErrors($log);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('FooController', $errors[0]);
    }

    public function test_extracts_entries_with_timezone_offset_in_timestamp(): void
    {
        $log = <<<LOG
[2024-01-15T10:00:00+05:30] local.ERROR: Timezone-aware error occurred
[2024-01-15T10:01:00+05:30] local.INFO: Request completed
LOG;

        $errors = LogParser::extractErrors($log);

        $this->assertCount(1, $errors);
        $this->assertStringContainsString('ERROR', $errors[0]);
    }
}
