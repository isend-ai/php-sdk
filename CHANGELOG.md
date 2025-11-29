# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2025-11-29

### Changed
- `sendTelegramTemplate()` now accepts a template variable (string) instead of template ID (int)
- `sendTemplateByEmail()` (deprecated) updated to match the new signature
- API request now sends `template` parameter instead of `template_id` for Telegram templates

## [1.0.0] - 2025-07-12

### Added
- Initial release of isend.ai PHP SDK
- `ISendClient` class for sending emails via isend.ai API

- Support for template-based email sending with `sendEmail()` method
- Comprehensive documentation and examples
- Basic test suite
- MIT License

### Features
- Simple API for sending template-based emails through isend.ai
- Support for template variables via dataMapping
- Error handling with descriptive exception messages
- Lightweight implementation using cURL (no external dependencies)
- Configurable timeout options 