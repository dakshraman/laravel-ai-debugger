<?php

namespace Dakshraman\AIDebugger\Tests;

use Illuminate\Console\Command;
use Dakshraman\AIDebugger\AI\AIInterface;
use Dakshraman\AIDebugger\Services\DebugAnalyzer;

class AnalyzeCommandTest extends TestCase
{
    private string $tmpFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'laravel_log_');
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tmpFile)) {
            unlink($this->tmpFile);
        }

        parent::tearDown();
    }

    public function test_returns_failure_when_log_file_not_found(): void
    {
        $this->artisan('debug:analyze', ['--file' => '/nonexistent/path/laravel.log'])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_returns_success_and_warns_when_log_file_is_empty(): void
    {
        // tempnam creates a 0-byte file.
        $this->artisan('debug:analyze', ['--file' => $this->tmpFile])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_analyzes_log_and_outputs_json_on_success(): void
    {
        file_put_contents(
            $this->tmpFile,
            '[2024-01-15 10:00:00] local.ERROR: Something went wrong'
        );

        $mockDriver = new class implements AIInterface {
            public function analyze(string $input): string
            {
                return json_encode([
                    'root_cause' => 'Test root cause',
                    'fix'        => 'Apply the fix',
                    'steps'      => ['Step 1'],
                ]);
            }
        };

        $this->app->instance(DebugAnalyzer::class, new DebugAnalyzer($mockDriver));

        $this->artisan('debug:analyze', ['--file' => $this->tmpFile])
            ->assertExitCode(Command::SUCCESS);
    }

    public function test_uses_configured_log_path_when_no_file_option_given(): void
    {
        config(['ai-debugger.log_path' => '/nonexistent/default.log']);

        $this->artisan('debug:analyze')
            ->assertExitCode(Command::FAILURE);
    }

    public function test_returns_failure_and_shows_error_when_ai_driver_throws(): void
    {
        file_put_contents(
            $this->tmpFile,
            '[2024-01-15 10:00:00] local.ERROR: Something went wrong'
        );

        $mockDriver = new class implements AIInterface {
            public function analyze(string $input): string
            {
                throw new \RuntimeException('Process exited with code 1: API key not configured');
            }
        };

        $this->app->instance(DebugAnalyzer::class, new DebugAnalyzer($mockDriver));

        $this->artisan('debug:analyze', ['--file' => $this->tmpFile])
            ->assertExitCode(Command::FAILURE);
    }

    public function test_warns_when_ai_driver_returns_no_structured_analysis(): void
    {
        file_put_contents(
            $this->tmpFile,
            '[2024-01-15 10:00:00] local.ERROR: Something went wrong'
        );

        $mockDriver = new class implements AIInterface {
            public function analyze(string $input): string
            {
                return 'No response';
            }
        };

        $this->app->instance(DebugAnalyzer::class, new DebugAnalyzer($mockDriver));

        $this->artisan('debug:analyze', ['--file' => $this->tmpFile])
            ->expectsOutputToContain('did not return a structured analysis')
            ->assertExitCode(Command::SUCCESS);
    }
}
