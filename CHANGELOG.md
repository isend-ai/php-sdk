# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2024-01-XX

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