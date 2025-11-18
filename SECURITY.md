# Security Policy

## Security Features

CommonsEventUploader implements multiple security layers to protect user data and prevent common web vulnerabilities.

### Authentication & Authorization

1. **OAuth 2.0 Integration**
   - Authorization Code Flow with Wikimedia
   - No local password storage
   - Secure token management

2. **Token Encryption**
   - OAuth tokens encrypted with AES-256-CBC
   - Unique initialization vectors (IV) for each token
   - Tokens stored encrypted in database
   - APP_KEY required for encryption/decryption

3. **Role-Based Access Control**
   - Three user roles: `user`, `conference_admin`, `super_admin`
   - Middleware enforces permissions on protected endpoints
   - Fine-grained access control per resource

### Web Security

1. **CSRF Protection**
   - CSRF tokens required for all state-changing requests
   - Token validation via CsrfMiddleware
   - Prevents cross-site request forgery attacks

2. **XSS Prevention**
   - Output escaping in frontend JavaScript
   - HTML entities encoded before display
   - No direct innerHTML injection of user data

3. **SQL Injection Prevention**
   - Prepared statements for all database queries
   - PDO with parameterized queries
   - No string concatenation in SQL

### File Upload Security

1. **File Validation**
   - Extension whitelist checking
   - MIME type validation using finfo
   - File size limits (100MB default)
   - Prevents malicious file uploads

2. **Allowed File Types**
   - Images: jpg, jpeg, png, gif, svg, webp, tiff
   - Documents: pdf
   - MIME type verification prevents extension spoofing

### Data Protection

1. **Session Security**
   - PHP session management
   - Session cookies with secure flags recommended
   - Session timeout implementation

2. **Sensitive Data Handling**
   - No plaintext token storage
   - Hashed passwords not used (OAuth only)
   - Database credentials in environment variables

## Configuration Requirements

### Required Environment Variables

```bash
# Generate a secure key
php generate-key.php

# Add to .env
APP_KEY=your_generated_key_here
DB_PASSWORD=secure_database_password
OAUTH_CLIENT_SECRET=your_oauth_secret
```

### Recommended Settings

1. **Production Environment**
   ```bash
   APP_ENV=production
   APP_DEBUG=false
   ```

2. **Database**
   - Use strong database passwords
   - Restrict database user permissions
   - Enable SSL/TLS for database connections

3. **Web Server**
   - Enable HTTPS/TLS
   - Set secure session cookie flags
   - Implement rate limiting
   - Use Content Security Policy headers

## Security Best Practices

### For Deployment

1. **Never commit** `.env` file to version control
2. **Rotate** OAuth credentials regularly
3. **Monitor** application logs for suspicious activity
4. **Update** dependencies regularly with `composer update`
5. **Backup** database regularly
6. **Use** firewall rules to restrict database access
7. **Enable** HTTPS for all production deployments

### For Developers

1. **Validate** all user input
2. **Sanitize** output before display
3. **Use** prepared statements for database queries
4. **Test** for security vulnerabilities regularly
5. **Follow** OWASP security guidelines
6. **Review** code changes for security implications

## Known Limitations

1. **Rate Limiting**: Not implemented - should be added for production
2. **Account Lockout**: Not implemented - consider adding after failed login attempts
3. **Audit Logging**: Basic logging only - consider comprehensive audit trails
4. **2FA**: Not implemented - OAuth provider handles authentication
5. **Password Policy**: Not applicable - uses OAuth only

## Reporting Security Vulnerabilities

If you discover a security vulnerability, please:

1. **Do not** open a public GitHub issue
2. Email the maintainers directly
3. Provide detailed information about the vulnerability
4. Allow time for the issue to be patched before public disclosure

## Security Updates

- Check for security updates regularly
- Subscribe to security advisories for PHP and dependencies
- Update to latest stable versions when security patches are released

## Compliance

This application implements:
- OWASP Top 10 protections
- Secure coding practices
- Data protection principles
- OAuth 2.0 specification compliance

## Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [OAuth 2.0 Security Best Practices](https://tools.ietf.org/html/draft-ietf-oauth-security-topics)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [Wikimedia OAuth Documentation](https://www.mediawiki.org/wiki/OAuth)
