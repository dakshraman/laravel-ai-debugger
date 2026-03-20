# Changelog

All notable changes to `laravel-ai-debugger` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.1] - 2026-03-20

### Fixed
- **Broken pipe on large log files** – `debug:analyze` threw `fwrite(): Write of N bytes failed with errno=32 Broken pipe` when the AI prompt exceeded the OS pipe buffer (~64 KB). The driver now uses non-blocking I/O and a `stream_select` loop to interleave 8 KB stdin writes with stdout/stderr reads, preventing the deadlock that caused the broken pipe.
- **Early stdin-close handled gracefully** – when a subprocess closes its stdin before all data is written (e.g. it exits immediately), `fwrite` returning `false` is now caught and writing stops cleanly instead of looping forever.
- **`proc_open` warning converted to `RuntimeException`** – if the configured AI binary does not exist, PHP emits a warning that Laravel's `HandleExceptions` converts to a fatal `ErrorException`. The call is now wrapped with `@proc_open` + `error_get_last()` so a proper `RuntimeException` is thrown instead.
- **Input truncation** – `buildPrompt()` now truncates the raw error trace to `maxInputBytes()` (default 32 768 bytes, overridable per driver) before building the prompt, providing a second line of defence against extremely large log payloads.

## [1.0.0] - 2026-03-20

### Added
- Initial stable release.
- Local-first AI debugging assistant for Laravel using CLI tools (no API key required).
- Support for **Claude**, **Gemini CLI**, **GitHub Copilot CLI**, and **OpenAI Codex** drivers.
- Artisan command `php artisan debug:analyze` to analyze Laravel log files.
- `AIDebugger` facade for inline usage.
- Extensible driver system via `AIInterface`.
- Auto-discovery compatible service provider and facade.
- Configurable via `.env` (`AI_DEBUGGER_DRIVER`, `AI_DEBUGGER_LOG_PATH`).
- Publishable config file (`config/ai-debugger.php`).
- Compatible with PHP 8.1+ and Laravel 10, 11, and 12.

[1.0.1]: https://github.com/dakshraman/laravel-ai-debugger/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/dakshraman/laravel-ai-debugger/releases/tag/v1.0.0
