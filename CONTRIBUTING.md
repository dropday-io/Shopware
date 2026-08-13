# Contributing to Dropday Shopware Integration

First off, thank you for considering contributing to the Dropday Shopware Integration! It's people like you that make this plugin better for everyone.

## Code of Conduct

By participating in this project, you agree to abide by our code of conduct: be respectful, inclusive, and constructive in all interactions.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates. When creating a bug report, include:

- **Clear title** describing the issue
- **Steps to reproduce** the behavior
- **Expected behavior** vs. actual behavior
- **Screenshots** if applicable
- **Environment details**:
  - Shopware version
  - PHP version
  - Plugin version
  - Browser (for admin issues)

### Suggesting Features

Feature suggestions are welcome! Please:

1. Check if the feature has already been suggested
2. Create an issue with `[Feature Request]` prefix
3. Describe the feature and its use case
4. Explain why it would benefit other users

### Pull Requests

1. **Fork** the repository
2. **Create a branch** from `main`:
   ```bash
   git checkout -b feature/your-feature-name
   ```
3. **Make your changes** following our coding standards
4. **Test your changes** thoroughly
5. **Commit** with clear messages:
   ```bash
   git commit -m "Add: Description of what you added"
   git commit -m "Fix: Description of what you fixed"
   ```
6. **Push** to your fork
7. **Open a Pull Request** with:
   - Clear description of changes
   - Reference to related issues
   - Screenshots for UI changes

## Development Setup

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 18+
- Docker (optional, for local development)

### Local Development

1. Clone your fork:
   ```bash
   git clone https://github.com/YOUR-USERNAME/Shopware.git
   cd Shopware
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Start development environment:
   ```bash
   docker compose up -d
   ```

4. Build administration assets:
   ```bash
   ./bin/build-administration.sh
   ```

### Coding Standards

#### PHP

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use strict types: `declare(strict_types=1);`
- Add PHPDoc blocks for classes and methods
- Use type hints for parameters and return types

```php
<?php

declare(strict_types=1);

namespace Dropday\DropdayIntegration\Service;

/**
 * Class description
 */
class MyService
{
    /**
     * Method description
     */
    public function doSomething(string $param): array
    {
        // Implementation
    }
}
```

#### JavaScript

- Follow Shopware's administration coding guidelines
- Use ES6+ features
- Add JSDoc comments for functions

#### Translations

- Always provide both `en-GB` and `de-DE` translations
- Use the translation key format: `dropday-integration.module.key`

### Testing

Before submitting a PR:

1. Ensure the plugin installs without errors
2. Test the configuration page
3. Test order synchronization (manual and automatic)
4. Verify translations are correct
5. Check browser console for JavaScript errors

### Commit Messages

Use semantic commit messages:

- `Add:` New feature
- `Fix:` Bug fix
- `Update:` Update existing functionality
- `Remove:` Remove feature or code
- `Refactor:` Code refactoring
- `Docs:` Documentation changes
- `Style:` Code style changes (formatting, etc.)
- `Test:` Test additions or changes

## Questions?

Feel free to reach out:

- Create an issue for general questions
- Email: [support@dropday.io](mailto:support@dropday.io)

Thank you for contributing! 🎉

