#!/bin/sh
set -eu

cd /var/www/html

PLUGIN_NAME="DropdayShopware"
PLUGIN_DIR="/var/www/html/custom/plugins/${PLUGIN_NAME}"
READY_MARKER="/tmp/dropday-plugin-ready"

rm -f "${READY_MARKER}"

echo "Dropday demo: checking plugin mount at ${PLUGIN_DIR}..."
if [ ! -f "${PLUGIN_DIR}/composer.json" ]; then
  echo "Dropday demo: ERROR — ${PLUGIN_DIR}/composer.json not found. Bind mount missing?" >&2
  ls -la /var/www/html/custom/plugins/ >&2 || true
  exit 1
fi

ls -la "${PLUGIN_DIR}"

# Bind mounts from CI/host often aren't owned by www-data
if command -v sudo >/dev/null 2>&1; then
  sudo chown -R www-data:www-data "${PLUGIN_DIR}" || true
fi

echo "Dropday demo: waiting for Shopware console..."
i=0
while [ "$i" -lt 90 ]; do
  if php bin/console plugin:list >/dev/null 2>&1; then
    break
  fi
  i=$((i + 1))
  sleep 2
done

if ! php bin/console plugin:list >/dev/null 2>&1; then
  echo "Dropday demo: Shopware console not ready after waiting" >&2
  exit 1
fi

echo "Dropday demo: refreshing plugins..."
php bin/console plugin:refresh

LIST_OUT=$(php bin/console plugin:list 2>/dev/null || true)
echo "${LIST_OUT}"

if ! echo "${LIST_OUT}" | grep -qi "${PLUGIN_NAME}"; then
  echo "Dropday demo: ERROR — ${PLUGIN_NAME} not discovered after plugin:refresh" >&2
  exit 1
fi

if echo "${LIST_OUT}" | grep -i "${PLUGIN_NAME}" | grep -q 'Yes'; then
  echo "Dropday demo: ${PLUGIN_NAME} already installed — updating and activating..."
  php bin/console plugin:update "${PLUGIN_NAME}" || true
  php bin/console plugin:activate "${PLUGIN_NAME}"
else
  echo "Dropday demo: installing and activating ${PLUGIN_NAME}..."
  php bin/console plugin:install --activate "${PLUGIN_NAME}"
fi

if [ -n "${DROPDAY_ACCOUNT_ID:-}" ]; then
  echo "Dropday demo: setting accountId..."
  php bin/console system:config:set DropdayShopware.config.accountId "${DROPDAY_ACCOUNT_ID}"
fi

if [ -n "${DROPDAY_API_KEY:-}" ]; then
  echo "Dropday demo: setting apiKey..."
  php bin/console system:config:set DropdayShopware.config.apiKey "${DROPDAY_API_KEY}"
fi

if [ -n "${DROPDAY_BASE_URL:-}" ]; then
  echo "Dropday demo: setting baseUrl..."
  php bin/console system:config:set DropdayShopware.config.baseUrl "${DROPDAY_BASE_URL}"
fi

if [ -n "${DROPDAY_LIVE_MODE:-}" ]; then
  echo "Dropday demo: setting liveMode=${DROPDAY_LIVE_MODE}..."
  php bin/console system:config:set DropdayShopware.config.liveMode "${DROPDAY_LIVE_MODE}"
fi

echo "Dropday demo: clearing cache..."
php bin/console cache:clear

touch "${READY_MARKER}"
echo "Dropday demo: ${PLUGIN_NAME} is ready."
