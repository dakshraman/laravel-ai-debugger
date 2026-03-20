# Changelog

All notable changes to `laravel-ai-debugger` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.0.0]: https://github.com/dakshraman/laravel-ai-debugger/releases/tag/v1.0.0
