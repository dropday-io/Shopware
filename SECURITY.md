# Security Policy

## Supported Versions

| Version | Supported          |
| ------- | ------------------ |
| 1.0.x   | :white_check_mark: |

## Reporting a Vulnerability

We take security seriously at Dropday. If you discover a security vulnerability in this plugin, please report it responsibly.

### How to Report

**DO NOT** create a public GitHub issue for security vulnerabilities.

Instead, please email us at: **[security@dropday.io](mailto:security@dropday.io)**

Include the following in your report:

1. **Description** of the vulnerability
2. **Steps to reproduce** the issue
3. **Potential impact** of the vulnerability
4. **Suggested fix** (if any)

### What to Expect

- **Acknowledgment:** Within 48 hours
- **Initial Assessment:** Within 5 business days
- **Resolution Timeline:** Depends on severity
  - Critical: 24-48 hours
  - High: 1 week
  - Medium: 2-4 weeks
  - Low: Next scheduled release

### Safe Harbor

We support safe harbor for security researchers who:

- Make a good faith effort to avoid privacy violations, destruction of data, and interruption or degradation of our services
- Only interact with accounts you own or with explicit permission of the account holder
- Do not exploit a security issue for purposes other than verification
- Report the vulnerability to us before disclosing it publicly

We will not pursue legal action against researchers who discover and report vulnerabilities in accordance with this policy.

## Security Best Practices for Users

1. **Keep your plugin updated** to the latest version
2. **Secure your API credentials** - don't share your Dropday API key
3. **Use HTTPS** for your Shopware installation
4. **Review access permissions** regularly in your Shopware admin
5. **Enable API logging** only for debugging, disable in production

## Known Security Considerations

### API Credentials

- API credentials are stored in Shopware's system configuration
- Credentials are encrypted at rest by Shopware
- Credentials are transmitted securely via HTTPS to Dropday API

### Data Transmission

- All communication with Dropday API uses HTTPS/TLS
- Order data includes customer PII (names, addresses, emails)
- No credit card or payment details are transmitted

### Logging

- When enabled, API logs may contain order data
- Ensure log files are properly secured
- Disable logging in production environments

## Changelog

Security-related changes will be noted in the [CHANGELOG.md](CHANGELOG.md) with a `[Security]` tag.

