# Contributing to X Algorithm PHP

Thank you for your interest in contributing to this project! This document provides guidelines and instructions for contributing.

## Getting Started

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Setting Up Development Environment

1. Fork the repository on GitHub
2. Clone your fork locally:
   ```bash
   git clone https://github.com/YOUR_USERNAME/x-algorithm-php.git
   cd x-algorithm-php/php-implementation
   ```

3. Install dependencies:
   ```bash
   composer install
   ```

4. Run tests to ensure everything works:
   ```bash
   composer test
   ```

## Development Workflow

### 1. Create a Branch

Create a new branch for your feature or bugfix:

```bash
git checkout -b feature/your-feature-name
```

### 2. Make Changes

Make your changes following the coding standards:

- Follow PSR-12 coding style
- Use strict types (`declare(strict_types=1);`)
- Add type hints and return types
- Write docblocks for classes and methods

### 3. Run Tests

Before submitting, run the test suite:

```bash
composer test
```

### 4. Run Code Analysis

Check for static analysis issues:

```bash
composer analyze
```

### 5. Check Code Style

Ensure code follows the coding standards:

```bash
composer lint
```

To automatically fix code style issues:

```bash
composer lint-fix
```

## Coding Standards

This project follows [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.

### Naming Conventions

- **Classes**: PascalCase (e.g., `HomeMixerService`)
- **Methods**: camelCase (e.g., `getHomeMix`)
- **Properties**: camelCase (e.g., `$userId`)
- **Constants**: UPPER_SNAKE_CASE (e.g., `DEFAULT_LIMIT`)
- **Namespaces**: PascalCase (e.g., `XAlgorithm\Core`)

### File Organization

- One class per file
- File name matches class name
- Interfaces in `Interfaces/` subdirectories
- Tests mirror source directory structure

### Documentation

All public classes and methods should have docblocks:

```php
/**
 * Brief description of the class.
 *
 * Longer description if needed.
 */
class MyClass
{
    /**
     * Brief description of the method.
     *
     * @param Type $param Description
     * @return Type Description
     */
    public function myMethod(Type $param): Type
    {
        // ...
    }
}
```

## Submitting Changes

### Pull Request Process

1. Push your changes to your fork
2. Create a Pull Request against the `main` branch
3. Fill in the PR template with:
   - Clear description of changes
   - Testing performed
   - Any breaking changes

### Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Types:
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation only changes
- `style`: Code style changes
- `refactor`: Code refactoring
- `test`: Adding tests
- `chore`: Maintenance tasks

Example:
```
feat(pipeline): add new diversity filter

Add AuthorDiversityScorer to improve content variety
```

## Reporting Issues

When reporting issues, please include:

1. Clear description of the problem
2. Steps to reproduce
3. Expected behavior
4. Actual behavior
5. PHP version
6. Any error messages or logs

## Questions?

If you have questions, feel free to open an issue for discussion.
