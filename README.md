# ScytaleDroid-Web (MVP)

A read-only LAMP UI for exploring ScytaleDroid static-analysis results stored in MariaDB/MySQL.

## Goals

- App-first navigation: list Android apps → drill into an app → review Findings, Strings, and Permissions.
- Secure, read-only views backed by prepared PDO queries.
- Operator-friendly defaults with pagination, filters, and consistent layout helpers.

## Non-goals (for now)

- Authentication or role management.
- Write operations or pipeline administration.
- Advanced charts or dynamic analysis visualisations.

## Quickstart

1. **Requirements**
   - PHP 8.1+ with `pdo_mysql` (mysqlnd).
   - Web server (Apache/Nginx) configured to serve the project root.
   - MariaDB/MySQL instance populated with ScytaleDroid data.
2. **Configuration**
   - Update `database/db_core/db_config.php` with credentials for your environment. The repository ships with local-development defaults (`localhost`, `scytale` user).
   - Adjust `config/config.php` for deployment-specific settings such as `BASE_URL`.
3. **Database**
   - The UI reads from ScytaleDroid tables/views including:
     - `permission_audit_apps`
     - `permission_audit_snapshots`
     - `android_app_categories`
     - `android_app_definitions`
     - `static_findings_summary`
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
  db_lib/               # Query templates and feature functions
lib/                    # Shared helpers (guards, render, pager, layout)
pages/                  # Route controllers (index, view_app, tabs, about)
```

## Database Layer Pattern

1. **`db_utils.php`** – shared helpers for executing queries, building filters, and pagination.
2. **`db_queries.php`** – SQL string templates (no execution).
3. **`db_func.php`** – feature functions that compose helpers + SQL and are consumed by pages.

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

1. Add SQL templates to `database/db_lib/db_queries.php` and expose feature helpers from `database/db_lib/db_func.php`.
2. In new pages under `pages/`, guard request parameters via `lib/guards.php` and call the helpers in `db_func.php`.
3. Render data with the utilities in `lib/render.php`, and use `lib/pager.php` for pagination.

## Deployment Notes

- Serve over HTTPS with standard security headers (`X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`).
- Run the application using a database account with **SELECT-only** permissions.
- Rotate the credentials in `database/db_core/db_config.php` when deploying to new environments and restrict them to read-only permissions.

## Roadmap Highlights

- ✅ Apps Directory (search, category filter, pagination).
- 🚧 App detail hub with tabs for Findings, Strings, Permissions.
- ⏱️ Future: cross-app views (Categories, Snapshots) and diagnostics.

Contributions are welcome—see [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines.
