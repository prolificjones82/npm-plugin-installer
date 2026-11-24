# Changelog

All notable changes to the npm-plugin-installer project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Initial project setup with core functionality
- Support for installing npm and yarn packages via Composer
- Automatic `package.json` creation if missing
- Configuration-based package installation (production and development)
- Custom `package.json` patching via merge functionality
- Full documentation and examples

### Changed

### Deprecated

### Removed

### Fixed

### Security

---

## Project Notes

### Version Numbering

Versions are released following [Semantic Versioning](https://semver.org/):

- **MAJOR** version for incompatible API changes
- **MINOR** version for added functionality in a backward-compatible manner
- **PATCH** version for backward-compatible bug fixes

### Release Process

1. Update version in relevant files (composer.json if applicable)
2. Update CHANGELOG.md with changes
3. Create a git tag with version number (e.g., `v1.0.0`)
4. Push changes and tags to repository

---

## Future Plans

- Support for pnpm package manager
- Configuration validation and error messages
- Support for custom post-install scripts
- Dry-run mode for testing configurations
- Support for monorepo structures
