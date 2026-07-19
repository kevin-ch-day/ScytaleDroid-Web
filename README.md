# ScytaleDroid-Web

A read-only LAMP UI for exploring ScytaleDroid analysis results stored in MariaDB/MySQL.

## Goals

- App-first navigation: list Android apps, drill into an app, then review Findings, Strings, and Permissions.
- Runtime-deviation navigation: review persisted dynamic runs, network features, cohorts, and risk regimes.
- Secure, read-only views backed by prepared PDO queries.
- Operator-friendly defaults with pagination, filters, and consistent layout helpers.

## Non-goals (for now)

- Authentication or role management.
- Write operations or pipeline administration.
- Write operations or pipeline control.
- Advanced charts or interactive runtime visualizations.

## Quickstart

1. **Requirements**
   - PHP 8.1+ with `pdo_mysql` (mysqlnd).
   - Web server (Apache/Nginx) configured to serve the project root.
   - MariaDB/MySQL instance populated with ScytaleDroid data.
2. **Configuration**
   - Prefer environment variables for database credentials.
   - For local development, copy `database/db_core/db_config.example.php` to `database/db_core/db_config.php`; the local file is intentionally ignored by Git.
   - Never commit `database/db_core/db_config.php`. It is for local-only overrides.
   - Adjust `config/config.php` for deployment-specific settings such as `BASE_URL`.
3. **Database**
   - The UI reads from ScytaleDroid static exposure and runtime deviation tables.
   - Ensure recommended indexes exist (see `database/README.md`).
4. **First Run**
   - Visit `/pages/index.php` to load the Apps Directory.
   - Use the built-in filters to search by package, label, or category.

## Project Structure

```
assets/                 # CSS/JS and design tokens
config/                 # Non-secret configuration (BASE_URL, pagination defaults)
database/
  db_core/              # PDO engine & credentials (db_config.php consumed by db_engine.php)
  db_lib/               # Query loaders, query modules, and feature functions
lib/                    # Shared helpers (guards, render, pager, layout)
pages/                  # Route controllers (index, view_app, tabs, about)
```

## Database Layer Pattern

1. **`db_utils.php`** – shared helpers for executing queries, building filters, and pagination.
2. **`db_queries.php`** – compatibility loader for SQL string templates in `database/db_lib/db_queries/` (no execution).
3. **Feature helper loaders** – files such as `db_app_reads.php`, `db_dynamic.php`, and `db_permission_intel.php` load focused helper modules from matching subdirectories.
4. **`db_func.php`** – barrel include consumed by pages.

Pages never run raw SQL; they pull sanitized inputs from `lib/guards.php`, call `db_func.php`, and render escaped output via `lib/render.php`.

## Development Workflow

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding style.
- Keep commits focused and reference relevant issues when opening PRs.
- Run syntax checks before pushing:

  ```bash
  find . -name '*.php' -print0 | xargs -0 -n1 php -l
  ```

- Update documentation (this README, `database/README.md`, etc.) when changing behavior.

### Extending the UI

1. Add SQL templates to the relevant file under `database/db_lib/db_queries/` and expose feature helpers through the matching `database/db_lib/` helper loader.
2. In new pages under `pages/`, guard request parameters via `lib/guards.php` and call the helpers in `db_func.php`.
3. Render data with the utilities in `lib/render.php`, and use `lib/pager.php` for pagination.
4. Keep the database as the source of truth. JSON/CSV artifacts should be linked or summarized only when they are not represented in first-class tables.

## Deployment Notes

- Serve over HTTPS with standard security headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: same-origin`, `Permissions-Policy`, and HTTPS-only `Strict-Transport-Security`).
- Run the application using a database account with **SELECT-only** permissions.
- Do not expose the repository root directly without server-level deny rules. If the app remains under `/var/www/html/ScytaleDroid-Web`, install rules equivalent to `deploy/apache/ScytaleDroid-Web.conf`.
- Do not rely on `.htaccess` unless Apache has `AllowOverride` enabled for this directory.
- Keep `pages/diag.php` localhost-only by default; set `SCYTALEDROID_WEB_ENABLE_DIAG=1` only for trusted maintenance windows.
- Rotate any credentials that were previously copied from local development defaults.
- If you terminate TLS at a reverse proxy, set `SD_TRUST_PROXY_HEADERS=1` so HTTPS-aware headers and canonical URLs reflect the forwarded scheme/host safely.

## Roadmap Highlights

- ✅ Apps Directory (search, category filter, pagination).
- ✅ App detail hub with tabs for Findings, Strings, Permissions, and Dynamic runs.
- ✅ Runtime Deviation run index and dynamic run detail pages.
- 🚧 Cross-analysis views that combine static exposure and runtime deviation by package.
- ⏱️ Future: cohort detail, report/export bundle views, and richer runtime visualizations.

Contributions are welcome—see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.
