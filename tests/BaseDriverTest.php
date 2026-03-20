<?php

namespace Dakshraman\AIDebugger\Tests;

use Dakshraman\AIDebugger\AI\Drivers\BaseDriver;
use RuntimeException;

class BaseDriverTest extends TestCase
{
    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Concrete driver that uses PHP itself as the "AI" executable so tests run
     * without any real AI CLI installed.
     *
     * The $phpScript is passed as the -r argument to the PHP binary.
     * $maxBytes overrides maxInputBytes(); set to 0 to use the default (32768).
     */
    private function phpDriver(string $phpScript, int $maxBytes = 0): BaseDriver
    {
        return new class(PHP_BINARY, $phpScript, $maxBytes) extends BaseDriver {
            public function __construct(
                private readonly string $bin,
                private readonly string $script,
                private readonly int    $bytes,
            ) {}

            protected function executable(): string   { return $this->bin; }
            protected function executableArgs(): array { return ['-r', $this->script]; }
            protected function maxInputBytes(): int
            {
                return $this->bytes > 0 ? $this->bytes : parent::maxInputBytes();
            }

            // Expose protected helpers for white-box unit tests.
            public function callBuildPrompt(string $trace): string { return $this->buildPrompt($trace); }
            public function callMaxInputBytes(): int               { return $this->maxInputBytes(); }
        };
    }

    // ---------------------------------------------------------------------------
    // maxInputBytes
    // ---------------------------------------------------------------------------

    public function test_default_max_input_bytes_is_32768(): void
    {
        $driver = new class extends BaseDriver {
            protected function executable(): string { return PHP_BINARY; }
            public function getLimit(): int { return $this->maxInputBytes(); }
        };

        $this->assertSame(32768, $driver->getLimit());
    }

    public function test_max_input_bytes_can_be_overridden(): void
    {
        $driver = $this->phpDriver('// noop', maxBytes: 1024);

        $this->assertSame(1024, $driver->callMaxInputBytes());
    }

    // ---------------------------------------------------------------------------
    // buildPrompt – truncation
    // ---------------------------------------------------------------------------

    public function test_build_prompt_does_not_truncate_short_input(): void
    {
        $driver = $this->phpDriver('// noop');
        $input  = 'Short error message';

        $prompt = $driver->callBuildPrompt($input);

        $this->assertStringContainsString($input, $prompt);
        $this->assertStringNotContainsString('[... truncated ...]', $prompt);
    }

    public function test_build_prompt_truncates_input_exceeding_max_bytes(): void
    {
        $driver = $this->phpDriver('// noop', maxBytes: 100);
        $input  = str_repeat('x', 200);

        $prompt = $driver->callBuildPrompt($input);

        $this->assertStringContainsString('[... truncated ...]', $prompt);
        // The kept portion must be exactly maxInputBytes characters long.
        $this->assertStringContainsString(str_repeat('x', 100), $prompt);
        // No character from the discarded tail survives.
        $this->assertStringNotContainsString(str_repeat('x', 101), $prompt);
    }

    public function test_build_prompt_does_not_truncate_input_at_exact_limit(): void
    {
        $driver = $this->phpDriver('// noop', maxBytes: 50);
        $input  = str_repeat('y', 50); // exactly at the limit

        $prompt = $driver->callBuildPrompt($input);

        $this->assertStringNotContainsString('[... truncated ...]', $prompt);
        $this->assertStringContainsString(str_repeat('y', 50), $prompt);
    }

    public function test_build_prompt_contains_required_json_template(): void
    {
        $driver = $this->phpDriver('// noop');
        $prompt = $driver->callBuildPrompt('some error');

        $this->assertStringContainsString('"root_cause"', $prompt);
        $this->assertStringContainsString('"fix"', $prompt);
        $this->assertStringContainsString('"steps"', $prompt);
    }

    // ---------------------------------------------------------------------------
    // analyze – basic subprocess interaction
    // ---------------------------------------------------------------------------

    public function test_analyze_returns_output_from_subprocess(): void
    {
        $json   = json_encode(['root_cause' => 'test', 'fix' => 'ok', 'steps' => []]);
        $driver = $this->phpDriver("echo '{$json}';");

        $result = $driver->analyze('some error');

        $this->assertStringContainsString('root_cause', $result);
    }

    public function test_analyze_returns_no_response_when_subprocess_produces_no_output(): void
    {
        $driver = $this->phpDriver('// noop');

        $this->assertSame('No response', $driver->analyze('error'));
    }

    // ---------------------------------------------------------------------------
    // analyze – large-input / broken-pipe regression
    // ---------------------------------------------------------------------------

    public function test_analyze_handles_prompt_larger_than_os_pipe_buffer(): void
    {
        // This driver echoes stdin verbatim and bypasses buildPrompt so the raw
        // payload (100 KB – well above the typical 64 KB pipe buffer) hits the
        // OS pipe directly.
        $driver = new class extends BaseDriver {
            protected function executable(): string   { return PHP_BINARY; }
            protected function executableArgs(): array
            {
                return ['-r', 'echo stream_get_contents(STDIN);'];
            }
            // Raise the truncation ceiling so nothing is cut.
            protected function maxInputBytes(): int { return 200_000; }
            // Skip the prompt wrapper; send the raw payload.
            protected function buildPrompt(string $trace): string { return $trace; }
        };

        $largeInput = str_repeat('A', 100_000); // 100 KB

        $result = $driver->analyze($largeInput);

        $this->assertSame($largeInput, $result);
    }

    public function test_analyze_handles_subprocess_that_closes_stdin_early(): void
    {
        // Subprocess ignores stdin completely and just outputs a fixed string.
        // Before the fix, writing a big payload to such a subprocess caused errno=32.
        $driver = new class extends BaseDriver {
            protected function executable(): string   { return PHP_BINARY; }
            protected function executableArgs(): array
            {
                return ['-r', 'echo "done";'];
            }
            // Raise ceiling so the large payload actually reaches the pipe.
            protected function maxInputBytes(): int { return 200_000; }
            protected function buildPrompt(string $trace): string { return $trace; }
        };

        // 80 KB payload against a subprocess that ignores stdin.
        $result = $driver->analyze(str_repeat('B', 80_000));

        $this->assertSame('done', $result);
    }

    // ---------------------------------------------------------------------------
    // analyze – error handling
    // ---------------------------------------------------------------------------

    public function test_analyze_throws_runtime_exception_when_process_fails_to_start(): void
    {
        $driver = new class extends BaseDriver {
            protected function executable(): string { return '__no_such_binary_xyz__'; }
        };

        $this->expectException(RuntimeException::class);

        $driver->analyze('error');
    }
}
