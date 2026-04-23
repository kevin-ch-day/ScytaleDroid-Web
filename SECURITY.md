# Security Policy

## Supported Versions

ScytaleDroid-Web is currently in active development. Only the latest `work` branch receives security updates.

## Reporting a Vulnerability

Please report security issues privately to security@scytaledroid.local. Provide enough detail to reproduce the issue, including affected endpoints, sample requests, and the potential impact.

We will acknowledge receipt within 2 business days and coordinate a fix timeline. Do not disclose vulnerabilities publicly until a fix has been released.

## Scope & Expectations

* The application is read-only and should run with a least-privilege database user (SELECT access only).
* Prefer `SCYTALEDROID_DB_*` environment variables for credentials. Local `database/db_core/db_config.php` files are ignored by Git and should not be committed.
* Production deployments should be fronted by HTTPS and configured with secure headers (e.g., `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`).
* Do not serve the repository root without deny rules for `.git`, `config`, `database`, `lib`, and project docs. If Apache has `AllowOverride None`, `.htaccess` will not protect these paths; install equivalent vhost/server rules such as `deploy/apache/ScytaleDroid-Web.conf`.
* `pages/diag.php` is localhost-only by default. Enable it remotely only during trusted maintenance by setting `SCYTALEDROID_WEB_ENABLE_DIAG=1`.

Thank you for helping keep ScytaleDroid-Web secure.
