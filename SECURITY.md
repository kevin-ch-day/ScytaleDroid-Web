# Security Policy

## Supported Versions

ScytaleDroid-Web is currently in active development. Only the latest `work` branch receives security updates.

## Reporting a Vulnerability

Please report security issues privately to security@scytaledroid.local. Provide enough detail to reproduce the issue, including affected endpoints, sample requests, and the potential impact.

We will acknowledge receipt within 2 business days and coordinate a fix timeline. Do not disclose vulnerabilities publicly until a fix has been released.

## Scope & Expectations

* The application is read-only and should run with a least-privilege database user (SELECT access only).
* Update the credentials in `database/db_core/db_config.php` to suit your deployment and restrict them to read-only access. Rotate credentials regularly and avoid reusing the development defaults in production.
* Production deployments should be fronted by HTTPS and configured with secure headers (e.g., `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`).

Thank you for helping keep ScytaleDroid-Web secure.
