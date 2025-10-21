# Contributing to ScytaleDroid-Web

Thanks for your interest in improving ScytaleDroid-Web! This project exposes ScytaleDroid static-analysis data through a read-only PHP UI. The guidelines below keep the codebase consistent and safe for operators.

## Getting Started

1. Clone the repository and install dependencies (Apache/Nginx with PHP 8.1+, MariaDB/MySQL client).
2. Review `database/db_core/db_config.php` and adjust the credentials for your environment. The committed defaults target a local developer instance (localhost, `scytale` user). Avoid committing production secrets to shared branches.
3. Ensure PHP has the `pdo_mysql` extension installed.
4. Configure a virtual host that points to the project root. The UI entry point is `pages/index.php`.

## Branches & Workflow

* Fork or create a feature branch from `work`.
* Keep commits scoped and descriptive (imperative mood, e.g., `Add pagination helper`).
* Open a Pull Request early for feedback when possible.

## Coding Standards

* Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) for PHP files.
* Use the established database abstraction: pages call functions in `database/db_lib/db_func.php`, which reuse SQL templates from `db_queries.php` and helpers from `db_utils.php`.
* Escape all HTML output with `lib/render.php::e()`.
* Validate and sanitize request parameters with the helpers in `lib/guards.php`.
* Pagination should use `lib/pager.php` helpers.

## Testing & Checks

Before opening a PR, run:

```bash
php -l index.php
find pages lib database -name '*.php' -print0 | xargs -0 -n1 php -l
```

If you introduce Composer dependencies, include instructions in the PR and update this section.

## Documentation

* Update `README.md` and relevant docs when behavior or configuration changes.
* Add screenshots (or describe output) for notable UI updates.

## Pull Request Expectations

* Describe the change, testing performed, and any database requirements.
* Note any configuration or migration steps for operators.

We appreciate your contributions! For questions, open a discussion or reach out to the maintainers listed in `SECURITY.md`.
