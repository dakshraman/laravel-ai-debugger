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

        $process = proc_open($command, $descriptors, $pipes);

        if (! is_resource($process)) {
            throw new RuntimeException('Failed to start process: ' . $this->executable());
        }

        fwrite($pipes[0], $prompt);
        fclose($pipes[0]);

        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        // Drain stderr so the child process never blocks waiting for the parent to read it.
        stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        return $output !== false && $output !== '' ? $output : 'No response';
    }

    protected function buildPrompt(string $trace): string
    {
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
