# Contributing to DashClip Delivery

Thank you for considering contributing to DashClip Delivery! 🎉

## Code of Conduct

Be respectful, constructive, and professional. We're all here to build great software together.

---

## How to Contribute

### Reporting Bugs

Use the [GitHub Issues](https://github.com/N3XT0R/dashclip-delivery/issues) to report bugs:

- Use a clear, descriptive title
- Describe the exact steps to reproduce
- Include your environment (PHP version, OS, etc.)
- Add error messages and logs if available

### Suggesting Features

Open a GitHub issue with the `enhancement` label:

- Explain the use case and problem it solves
- Describe your proposed solution
- Consider backward compatibility

### Pull Requests

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass: `composer test`
6. Commit with clear messages
7. Push to your fork
8. Open a Pull Request

---

## Development Setup

```bash
# Clone your fork
git clone https://github.com/YOUR-USERNAME/dashclip-delivery.git
cd dashclip-delivery

# Install dependencies
composer install
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Run tests
composer test
```

---

## Coding Standards

- Follow **PSR-12** coding style
- Use **type hints** for all parameters and return types
- Write **PHPDoc** for all public methods
- Keep methods **small and focused** (single responsibility)
- Write **tests** for new features
- Use **meaningful variable names**

Run code formatting before committing:

```bash
composer format  # If you have a formatter configured
```

---

## Testing

All contributions should include tests:

```bash
# Run all tests
composer test

# Run specific test
php artisan test --filter=IngestScanTest

# Run with coverage
php artisan test --coverage
```

Test types:

- **Unit tests**: `tests/Unit/`
- **Feature tests**: `tests/Feature/`
- **Integration tests**: For services with external dependencies

---

## Contributor License Agreement (CLA)

### Why we need a CLA

DashClip Delivery uses a **dual-licensing model**:

- **AGPL-3.0** for the open source community
- **Commercial License** for proprietary use cases

This allows:

- ✅ Sustainable project maintenance
- ✅ Free use for open source projects
- ✅ Professional support options
- ✅ Continued development funding

### What you agree to by contributing

By submitting a contribution (code, documentation, etc.), you agree that:

1. **Your contribution is your original work** or you have the right to submit it
2. **You license your contribution under AGPL-3.0** to the open source project
3. **You grant the project maintainer(s) the right** to sublicense your contribution under both AGPL-3.0 and commercial
   licenses
4. **You retain copyright** of your contribution
5. **You provide your contribution "as-is"** without warranties

### How to indicate agreement

Add this to your Pull Request description:

```
I agree to the Contributor License Agreement as outlined in CONTRIBUTING.md
```

Or sign your commit:

```bash
git commit -s -m "Add feature X"
```

The `-s` flag adds a "Signed-off-by" line, indicating your agreement.

---

## Recognition

All contributors will be:

- Listed in the project README
- Credited in release notes
- Mentioned in commit messages

Significant contributions may be recognized with:

- Co-maintainer status
- Direct communication channel
- Input on project direction

---

## Questions?

- 💬 Open a [GitHub Discussion](https://github.com/N3XT0R/dashclip-delivery/discussions)
- 📧 Email: info@php-dev.info
- 🐛 Bug reports: [GitHub Issues](https://github.com/N3XT0R/dashclip-delivery/issues)

---

## License

By contributing, you agree that your contributions will be licensed under both:

- GNU Affero General Public License v3.0 (AGPL-3.0)
- Commercial License (for maintainer use only)

See [LICENSE](LICENSE) and [LICENSE-COMMERCIAL.md](LICENSE-COMMERCIAL.md) for details.

---

Thank you for making DashClip Delivery better! 🚀