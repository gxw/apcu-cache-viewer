# Contributing to APCu Cache Viewer

Thank you for considering contributing to APCu Cache Viewer! We appreciate your time and effort to help improve this project.

## 🚀 Getting Started

1. **Fork** the repository on GitHub
2. **Clone** your forked repository
   ```bash
   git clone https://github.com/gxw/apcu-cache-viewer.git
   cd apcu-cache-viewer
   ```
3. **Set up** the development environment
   ```bash
   # Install dependencies
   composer install
   
   # Copy environment file
   cp .env.example .env
   
   # Generate application key
   php -r "echo 'APP_KEY=' . base64_encode(random_bytes(32)) . PHP_EOL;" >> .env
   ```

## 🔧 Development Workflow

1. Create a new branch for your feature or bugfix
   ```bash
   git checkout -b feature/your-feature-name
   # or
   git checkout -b bugfix/issue-number-description
   ```

2. Make your changes following the coding standards

3. Run tests
   ```bash
   # Run PHPUnit tests
   composer test
   
   # Run PHPStan for static analysis
   composer stan
   
   # Run PHP CS Fixer
   composer cs-fix
   ```

4. Commit your changes with a descriptive message
   ```bash
   git add .
   git commit -m "Add your descriptive commit message"
   ```

5. Push to your fork
   ```bash
   git push origin your-branch-name
   ```

6. Open a **Pull Request** against the `main` branch

## 📝 Coding Standards

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standard
- Use type hints and return type declarations where possible
- Write PHPDoc blocks for all classes, methods, and properties
- Keep methods small and focused on a single responsibility
- Write tests for new features and bug fixes

## 🧪 Testing

We use PHPUnit for testing. Write tests for any new functionality and ensure all tests pass before submitting a pull request.

```bash
# Run all tests
composer test

# Run a specific test file
./vendor/bin/phpunit tests/Feature/YourTest.php

# Run with coverage (requires Xdebug or PCOV)
composer test-coverage
```

## 🐛 Reporting Bugs

When reporting bugs, please include:

1. A clear, descriptive title
2. Steps to reproduce the issue
3. Expected vs. actual behavior
4. PHP version and environment details
5. Any relevant error messages or logs

## 💡 Feature Requests

We welcome feature requests! Please:

1. Check if the feature has already been requested
2. Describe the problem you're trying to solve
3. Explain why this feature would be valuable
4. Provide any relevant examples or mockups

## 🛡️ Security Vulnerabilities

If you discover a security vulnerability, please email security@example.com instead of creating an issue. All security vulnerabilities will be promptly addressed.

## 📜 License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).

## 🙏 Thank You!

Your contributions make open-source software amazing. Thank you for being part of our community!

---

<p align="center">
  Made with ❤️ by the APCu Cache Viewer contributors
</p>
