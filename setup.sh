#!/usr/bin/env bash
set -euo pipefail

# --- Settings ---------------------------------------------------------------
# Flip to 1 if you also want phpMyAdmin installed from Fedora repos.
INSTALL_PHPMYADMIN=0

WEB_ROOT="${SCYTALEDROID_WEB_ROOT:-/var/www/html}"

say() { printf '[*] %s\n' "$*"; }
ok()  { printf '[✔] %s\n' "$*"; }
die() { printf '[✘] %s\n' "$*" >&2; exit 1; }

php_has_pdo_mysql() {
  # Do not use grep -q here: with pipefail, its early exit can make PHP report SIGPIPE.
  php -m 2>/dev/null | grep -i '^pdo_mysql$' >/dev/null
}

# --- Packages ---------------------------------------------------------------
PKGS=(
  httpd
  php
  php-cli
  php-common
  php-pdo
  php-mysqlnd
  php-mbstring
  php-xml
  php-gd
  mariadb-connector-c
  rsync
  unzip
  policycoreutils-python-utils   # semanage/restorecon on Fedora
  firewalld
  git
)

usage() {
  cat <<'EOF'
Usage: ./setup.sh [--check]

  --check    Run a side-effect-free Fedora/PHP deployment preflight.
EOF
}

preflight() {
  local missing=0
  [[ "$WEB_ROOT" = /* ]] || die "SCYTALEDROID_WEB_ROOT must be an absolute path."
  command -v dnf >/dev/null || die "This script expects Fedora (dnf not found)."

  say "Checking Fedora packages..."
  for package in "${PKGS[@]}"; do
    if rpm -q "$package" >/dev/null 2>&1; then
      ok "Package available: $package"
    else
      printf '[!] Missing package: %s\n' "$package" >&2
      missing=1
    fi
  done

  if php_has_pdo_mysql; then
    ok "PHP PDO MySQL driver is available."
  else
    printf '[!] PHP PDO MySQL driver is unavailable.\n' >&2
    missing=1
  fi

  if [[ -d "$WEB_ROOT" ]]; then
    ok "Web root exists: $WEB_ROOT"
  else
    printf '[!] Web root is absent: %s (setup will create it)\n' "$WEB_ROOT" >&2
  fi

  if [[ -f "$WEB_ROOT/info.php" ]]; then
    printf '[!] Remove public diagnostic endpoint before exposure: %s/info.php\n' "$WEB_ROOT" >&2
    missing=1
  fi

  if [[ -f deploy/apache/ScytaleDroid-Web.conf ]]; then
    ok "Apache hardening include is present."
  else
    printf '[!] Missing Apache hardening include: deploy/apache/ScytaleDroid-Web.conf\n' >&2
    missing=1
  fi

  if (( missing != 0 )); then
    die "Preflight found required remediation. Run ./setup.sh or resolve the listed items."
  fi
  ok "Preflight passed. Database credentials and database reachability are checked separately."
}

case "${1:-}" in
  --check)
    preflight
    exit 0
    ;;
  --help|-h)
    usage
    exit 0
    ;;
  '')
    ;;
  *)
    usage >&2
    die "Unknown option: $1"
    ;;
esac

[[ "$WEB_ROOT" = /* ]] || die "SCYTALEDROID_WEB_ROOT must be an absolute path."
command -v dnf >/dev/null || die "This script expects Fedora (dnf not found)."

# --- Privilege escalation ---------------------------------------------------
if [[ $EUID -ne 0 ]]; then
  exec sudo -E bash "$0" "$@"
fi

say "Installing packages…"
dnf -y install "${PKGS[@]}"

if [[ "$INSTALL_PHPMYADMIN" -eq 1 ]]; then
  say "Installing phpMyAdmin…"
  dnf -y install phpMyAdmin
fi
ok "Packages installed."

php_has_pdo_mysql \
  || die "PHP PDO MySQL driver is unavailable after package installation. Verify php-mysqlnd and the active PHP configuration."
ok "PHP PDO MySQL driver is available."

# --- Services ---------------------------------------------------------------
say "Enabling and starting Apache (httpd)…"
systemctl enable --now httpd
ok "Apache is running."

say "Enabling and starting firewalld…"
systemctl enable --now firewalld || true
if systemctl is-active --quiet firewalld; then
  say "Opening HTTP/HTTPS in firewalld…"
  firewall-cmd --add-service=http --permanent || true
  firewall-cmd --add-service=https --permanent || true
  firewall-cmd --reload || true
  ok "Firewall updated."
else
  say "firewalld inactive; skipping firewall rules."
fi

# --- SELinux (allow Apache to connect to DB over the network) --------------
if command -v setsebool >/dev/null 2>&1; then
  say "Setting SELinux boolean httpd_can_network_connect_db=1…"
  setsebool -P httpd_can_network_connect_db 1 || true
  ok "SELinux boolean applied."
fi

# --- Web root sanity --------------------------------------------------------
say "Ensuring web root exists: $WEB_ROOT"
mkdir -p "$WEB_ROOT"
restorecon -Rv "$WEB_ROOT" >/dev/null 2>&1 || true

if [[ -f "$WEB_ROOT/info.php" ]]; then
  say "Existing $WEB_ROOT/info.php detected; remove it before exposing this host publicly."
fi

# --- Summary ---------------------------------------------------------------
ok "Setup complete."

cat <<EOF

Next steps:

1) Verify PHP and Apache:
   - systemctl is-active httpd
   - php -m | grep -i '^pdo_mysql$'

2) Deploy your app beneath the configured web root:
   $WEB_ROOT/ScytaleDroid-Web

3) Configure the deployment base path if needed:
   - set SD_BASE_URL=/ScytaleDroid-Web for a subdirectory deployment, or
   - leave it unset to auto-detect the path.

4) Database config:
   - Prefer SCYTALEDROID_DB_* environment variables, or
   - copy database/db_core/db_config.example.php to database/db_core/db_config.php
   - local db_config.php is ignored by Git and must not be committed

5) Test the app:
   - http://localhost/ScytaleDroid-Web/pages/index.php
   - http://localhost/ScytaleDroid-Web/assets/css/main_style.css (should be 200)
   - install deploy/apache/ScytaleDroid-Web.conf or equivalent server rules before public exposure

EOF
