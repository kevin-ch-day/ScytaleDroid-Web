# Agent Instructions

These rules apply to the entire repository.

## Coding standards
- Follow PSR-12 for structure and naming, but keep the two-space indentation defined in `.editorconfig`.
- Route all database access through the helpers in `database/db_lib/` (`db_utils.php`, `db_queries.php`, `db_func.php`). Pages must not execute raw SQL.
- Escape output with `lib/render.php::e()` and validate request parameters with `lib/guards.php`.

## Adding features
- Place new SQL text in `database/db_lib/db_queries.php` and expose it through `database/db_lib/db_func.php` using `sql_filters()`/`db_paged()` as needed.
- Pages in `pages/` should only call functions in `db_func.php`, sanitize inputs via `lib/guards.php`, and render output with helpers in `lib/render.php` and `lib/pager.php`.

## Testing
- Run `find pages lib database -name '*.php' -print0 | xargs -0 -n1 php -l` before committing PHP changes.

## Documentation
- Update `README.md` or `database/README.md` when behavior or setup steps change.
- Keep `database/db_core/db_config.php` focused on local-development defaults; mention overrides in docs instead of changing the file per-environment.

## Pull requests
- Provide a short summary of changes and list the syntax check(s) executed.
