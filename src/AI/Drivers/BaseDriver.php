<?php

namespace Dakshraman\AIDebugger\AI\Drivers;

use Dakshraman\AIDebugger\AI\AIInterface;
use RuntimeException;

/**
 * Base driver that pipes a prompt to an external CLI tool via proc_open,
 * avoiding shell interpolation of untrusted content.
 */
abstract class BaseDriver implements AIInterface
{
    /**
     * Bytes transferred per read/write call when streaming data to/from the subprocess.
     */
    private const CHUNK_SIZE = 8192;

    /**
     * Seconds stream_select() waits for a pipe to become ready before retrying.
     */
    private const STREAM_SELECT_TIMEOUT = 5;

    /**
     * Name of the CLI executable to invoke (e.g. "claude", "gemini").
     */
    abstract protected function executable(): string;

    /**
     * Optional extra arguments passed to the executable.
     *
     * @return string[]
     */
    protected function executableArgs(): array
    {
        return [];
    }

    public function analyze(string $input): string
    {
        $prompt  = $this->buildPrompt($input);
        $command = array_merge([$this->executable()], $this->executableArgs());

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        // Suppress the PHP warning that is emitted before proc_open returns false
        // when the binary doesn't exist, so that our is_resource() check works
        // reliably (Laravel's error handler converts warnings to ErrorException).
        error_clear_last();
        $process = @proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            $err = error_get_last();
            throw new RuntimeException(
                'Failed to start process: ' . $this->executable()
                . ($err !== null ? ' – ' . $err['message'] : '')
            );
        }

        // Use non-blocking I/O and interleave stdin writes with stdout reads to
        // prevent a deadlock / broken-pipe when the prompt is larger than the
        // OS pipe buffer (~64 KB on most systems).
        stream_set_blocking($pipes[0], false);
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $written   = 0;
        $total     = strlen($prompt);
        $output    = '';
        $stdinOpen = true;

        while ($stdinOpen || ! feof($pipes[1])) {
            $read   = [$pipes[1], $pipes[2]];
            $write  = $stdinOpen ? [$pipes[0]] : [];
            $except = null;

            if (stream_select($read, $write, $except, self::STREAM_SELECT_TIMEOUT) === false) {
                break;
            }

            // Write the next chunk to stdin.
            if ($stdinOpen && ! empty($write)) {
                $chunk = substr($prompt, $written, self::CHUNK_SIZE);
                // Use @ to suppress the PHP "Broken pipe" warning that is emitted
                // before fwrite() returns false when the subprocess closes stdin early.
                $bytes = @fwrite($pipes[0], $chunk);
                if ($bytes === false) {
                    // Subprocess closed its stdin early (e.g. broken pipe); stop writing.
                    fclose($pipes[0]);
                    $stdinOpen = false;
                } else {
                    $written += $bytes;
                    if ($written >= $total) {
                        fclose($pipes[0]);
                        $stdinOpen = false;
                    }
                }
            }

            // Drain stdout and stderr so the subprocess never blocks on its writes.
            foreach ($read as $pipe) {
                if ($pipe === $pipes[1]) {
                    $chunk = fread($pipe, self::CHUNK_SIZE);
                    if ($chunk !== false) {
                        $output .= $chunk;
                    }
                } else {
                    fread($pipe, self::CHUNK_SIZE);
                }
            }
        }

        if ($stdinOpen) {
            fclose($pipes[0]);
        }
        fclose($pipes[1]);
        // Switch stderr back to blocking so stream_get_contents drains it fully.
        stream_set_blocking($pipes[2], true);
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($output !== '') {
            return $output;
        }

        // On PHP < 8.3, proc_open() returns a live resource even when the binary
        // does not exist (the fork succeeds; execvp failure is deferred to the child).
        // The child process exits with code 127 (POSIX "command not found").
        // PHP 8.3+ returns false from proc_open() directly, which is caught above by
        // the is_resource() guard.  This check ensures consistent behaviour across
        // all supported PHP versions.
        if ($exitCode === 127) {
            throw new RuntimeException('Failed to start process: ' . $this->executable() . ' (command not found)');
        }

        return 'No response';
    }

    /**
     * Maximum number of bytes of the raw trace that will be included in the
     * prompt.  Keeping this well below typical CLI-tool limits avoids broken-pipe
     * errors on very large log files.  Subclasses may override as needed.
     */
    protected function maxInputBytes(): int
    {
        return 32768;
    }

    protected function buildPrompt(string $trace): string
    {
        if (strlen($trace) > $this->maxInputBytes()) {
            $trace = substr($trace, 0, $this->maxInputBytes()) . "\n[... truncated ...]";
        }

        return <<<PROMPT
You are a Laravel debugging expert.

Analyze this error:

{$trace}

Return JSON:
{
  "root_cause": "...",
  "fix": "...",
  "steps": []
}
PROMPT;
    }
}
