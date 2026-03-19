<?php

namespace Dakshraman\AIDebugger\Console;

use Illuminate\Console\Command;
use Dakshraman\AIDebugger\Services\DebugAnalyzer;
use Dakshraman\AIDebugger\Helpers\LogParser;

class AnalyzeCommand extends Command
{
    protected $signature = 'debug:analyze {--file= : Path to the log file to analyze}';

    protected $description = 'Analyze Laravel errors using local AI (Claude, Gemini, or Copilot)';

    /**
     * Maximum number of bytes read from the log file to keep memory usage bounded.
     */
    private const MAX_BYTES = 1_048_576; // 1 MiB

    public function handle(DebugAnalyzer $analyzer): int
    {
        $file = $this->option('file') ?? config('ai-debugger.log_path');

        if (! file_exists($file)) {
            $this->error("Log file not found: {$file}");

            return self::FAILURE;
        }

        $log = $this->readTail($file, self::MAX_BYTES);

        if (empty(trim($log))) {
            $this->warn('Log file is empty.');

            return self::SUCCESS;
        }

        $this->info('Analyzing log file with AI...');

        $entries = LogParser::extractErrors($log);
        $input   = empty($entries) ? $log : implode("\n---\n", $entries);

        $result = $analyzer->analyze($input);

        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }

    /**
     * Read up to $maxBytes from the end of the file to avoid loading huge logs into memory.
     */
    private function readTail(string $path, int $maxBytes): string
    {
        $size = filesize($path);

        if ($size === false || $size === 0) {
            return '';
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            return '';
        }

        $offset = max(0, $size - $maxBytes);
        fseek($handle, $offset);
        $content = fread($handle, $maxBytes);
        fclose($handle);

        return $content !== false ? $content : '';
    }
}
