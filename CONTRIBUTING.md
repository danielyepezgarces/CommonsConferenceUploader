# Contributing to CommonsEventUploader

Thank you for your interest in contributing to CommonsEventUploader! This document provides guidelines for contributing to the project.

## Getting Started

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/CommonsConferenceUploader.git`
3. Create a feature branch: `git checkout -b feature/your-feature-name`
4. Make your changes
5. Test your changes
6. Commit your changes: `git commit -m "Add your feature"`
7. Push to your fork: `git push origin feature/your-feature-name`
8. Create a Pull Request

## Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- MySQL 5.7 or higher
- Git

### Installation

```bash
# Clone the repository
git clone https://github.com/danielyepezgarces/CommonsConferenceUploader.git
cd CommonsConferenceUploader

# Install dependencies
composer install

# Configure environment
cp .env.example .env
php generate-key.php
# Add generated key to .env

# Create database
mysql -u root -p -e "CREATE DATABASE commons_uploader CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Run migrations
php migrate.php

# Start development server
php -S localhost:8000 -t public
```

## Code Style

### PHP

- Follow PSR-12 coding standards
- Use type hints for all parameters and return values
- Document public methods with PHPDoc comments
- Keep methods focused and single-purpose
- Use meaningful variable and method names

Example:
```php
/**
 * Find user by Wikimedia ID
 * 
 * @param string $wikimediaId The Wikimedia user ID
 * @return User|null The user object or null if not found
 */
public static function findByWikimediaId(string $wikimediaId): ?User
{
    // Implementation
}
```

### SQL

- Use uppercase for SQL keywords
- Use prepared statements for all queries
- Add appropriate indexes for performance
- Include foreign key constraints

### JavaScript

- Use modern ES6+ syntax
- Avoid global variables
- Use meaningful variable names
- Add comments for complex logic
- Escape HTML to prevent XSS

## Testing

Currently, the project does not have automated tests. When contributing:

1. Manually test your changes
2. Test both success and error cases
3. Test with different user roles
4. Verify security implications
5. Check for SQL injection vulnerabilities
6. Test XSS prevention

## Commit Messages

Write clear, descriptive commit messages:

- Use the imperative mood ("Add feature" not "Added feature")
- Keep the first line under 50 characters
- Add detailed description if needed
- Reference issue numbers when applicable

Good examples:
```
Add category search functionality

Implement autocomplete search for categories with debouncing
to reduce API calls. Includes AJAX endpoint and frontend UI.

Fixes #123
```

## Pull Request Process

1. **Update Documentation**: Ensure README, API docs, and comments are updated
2. **Test Thoroughly**: Verify all functionality works as expected
3. **Security Review**: Check for security vulnerabilities
4. **Code Quality**: Follow coding standards and best practices
5. **Description**: Provide clear description of changes
6. **Link Issues**: Reference related issues in the PR description

### PR Checklist

- [ ] Code follows project style guidelines
- [ ] All changes have been tested
- [ ] Documentation has been updated
- [ ] No security vulnerabilities introduced
- [ ] Commit messages are clear and descriptive
- [ ] PR description explains the changes

## Security

- Never commit sensitive data (.env file, credentials, etc.)
- Report security vulnerabilities privately
- Follow security best practices
- Use prepared statements for database queries
- Validate and sanitize all user input
- Escape output to prevent XSS

## Feature Requests

Before implementing a new feature:

1. Check if an issue already exists
2. Open a new issue to discuss the feature
3. Wait for maintainer feedback
4. Implement the feature after approval
5. Submit a pull request

## Bug Reports

When reporting bugs, include:

- Clear description of the bug
- Steps to reproduce
- Expected behavior
- Actual behavior
- PHP version and environment details
- Error messages and logs
- Screenshots if applicable

## Code Review

All contributions will be reviewed for:

- Code quality and style
- Security implications
- Performance considerations
- Documentation completeness
- Test coverage
- Compatibility

## License

By contributing, you agree that your contributions will be licensed under the same license as the project (MIT License).

## Questions?

If you have questions about contributing:

- Open a GitHub issue
- Check existing documentation
- Review closed issues and PRs

## Recognition

Contributors will be recognized in:
- GitHub contributors page
- Project documentation
- Release notes for significant contributions

Thank you for contributing to CommonsEventUploader!
