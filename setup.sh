#!/usr/bin/env bash
set -euo pipefail

# --- Settings ---------------------------------------------------------------
# Flip to 1 if you also want phpMyAdmin installed from Fedora repos.
INSTALL_PHPMYADMIN=0

WEB_ROOT="/var/www/html"

# --- Privilege escalation ---------------------------------------------------
if [[ $EUID -ne 0 ]]; then
  exec sudo -E bash "$0" "$@"
fi

say() { printf '[*] %s\n' "$*"; }
ok()  { printf '[✔] %s\n' "$*"; }
die() { printf '[✘] %s\n' "$*" >&2; exit 1; }

command -v dnf >/dev/null || die "This script expects Fedora (dnf not found)."

# --- Packages ---------------------------------------------------------------
PKGS=(
  httpd
  php
  php-cli
  php-common
  php-pdo
  php-mysqlnd
  php-json
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

say "Installing packages…"
dnf -y install "${PKGS[@]}"

if [[ "$INSTALL_PHPMYADMIN" -eq 1 ]]; then
  say "Installing phpMyAdmin…"
  dnf -y install phpMyAdmin
fi
ok "Packages installed."

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

# Drop a tiny phpinfo page if missing
if [[ ! -f "$WEB_ROOT/info.php" ]]; then
  cat > "$WEB_ROOT/info.php" <<'PHP'
<?php phpinfo();
PHP
  ok "Created $WEB_ROOT/info.php (temporary test page)."
fi

# --- Summary ---------------------------------------------------------------
ok "Setup complete."

cat <<EOF

Next steps:

1) Verify PHP and Apache:
   - Open:   http://localhost/info.php
   - Expect: PHP info page.

2) Deploy your app (already copied in your case):
   /var/www/html/ScytaleDroid-Web

3) Ensure your app config points to the right subdir:
   - config/config.php:   \$__BASE_URL = '/ScytaleDroid-Web';

4) Database config (hardcoded by design here):
   - database/db_core/db_config.php  (host, port, db, user, pass)

5) Test the app:
   - http://localhost/ScytaleDroid-Web/pages/index.php
   - http://localhost/ScytaleDroid-Web/assets/css/main_style.css (should be 200)

EOF
