# laravel-ai-debugger

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dakshraman/laravel-ai-debugger.svg?style=flat-square)](https://packagist.org/packages/dakshraman/laravel-ai-debugger)
[![Tests](https://github.com/dakshraman/laravel-ai-debugger/actions/workflows/tests.yml/badge.svg)](https://github.com/dakshraman/laravel-ai-debugger/actions/workflows/tests.yml)
[![License](https://img.shields.io/packagist/l/dakshraman/laravel-ai-debugger.svg?style=flat-square)](LICENSE)

A local-first AI debugging assistant for Laravel using CLI tools like **Claude**, **Gemini CLI**, **GitHub Copilot**, and **OpenAI Codex**. Zero API cost — everything runs through your locally installed CLI tools.

---

## Features

- ✅ Local AI-powered error analysis (no API key required)
- ✅ Works with Claude, Gemini CLI, GitHub Copilot, and OpenAI Codex
- ✅ Plug-and-play Laravel package with auto-discovery
- ✅ Artisan command: `php artisan debug:analyze`
- ✅ Facade for inline usage: `AIDebugger::analyze($trace)`
- ✅ Extensible driver system

---

## Requirements

- PHP 8.1+
- Laravel 10, 11, or 12
- One of the following CLI tools installed and in your `$PATH`:
  - [claude](https://github.com/anthropics/anthropic-tools) — Anthropic Claude CLI
  - [gemini](https://ai.google.dev/) — Google Gemini CLI
  - [gh copilot](https://docs.github.com/en/copilot/github-copilot-in-the-cli) — GitHub Copilot CLI
  - [codex](https://github.com/openai/codex) — OpenAI Codex CLI

---

## Installation

```bash
composer require dakshraman/laravel-ai-debugger
```

The service provider and facade are registered automatically via Laravel's package auto-discovery.

### Publish the config file

```bash
php artisan vendor:publish --tag=config
```

This publishes `config/ai-debugger.php`.

---

## Configuration

Set your preferred AI driver in `.env`:

```env
AI_DEBUGGER_DRIVER=claude   # Options: claude, gemini, copilot, codex
```

Or edit `config/ai-debugger.php` directly:

```php
return [
    'driver'   => env('AI_DEBUGGER_DRIVER', 'claude'),
    'log_path' => env('AI_DEBUGGER_LOG_PATH', storage_path('logs/laravel.log')),
];
```

---

## Usage

### Artisan Command

Analyze your Laravel log file:

```bash
php artisan debug:analyze
```

Point to a specific log file:

```bash
php artisan debug:analyze --file=/path/to/custom.log
```

### Facade

```php
use AIDebugger;

$result = AIDebugger::analyze($exception->getMessage());

// $result = [
//   'root_cause' => '...',
//   'fix'        => '...',
//   'steps'      => [...],
// ]
```

### Auto-hook (Optional)

Register in your `AppServiceProvider` to automatically analyze every reported exception:

```php
use Illuminate\Support\Facades\Log;

public function register(): void
{
    $this->app->reportable(function (\Throwable $e) {
        Log::info('AI Debug Analysis', app('ai-debugger')->analyze($e->getMessage()));
    });
}
```

---

## Extending with a Custom Driver

Implement `Dakshraman\AIDebugger\AI\AIInterface`:

```php
use Dakshraman\AIDebugger\AI\AIInterface;

class MyCustomDriver implements AIInterface
{
    public function analyze(string $input): string
    {
        // Call your AI tool and return the response string
        return shell_exec('echo ' . escapeshellarg($input) . ' | my-ai-tool');
    }
}
```

Then bind it in a service provider or extend `AIDriverManager`.

---

## License

MIT
