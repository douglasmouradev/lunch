#!/bin/bash
# Permissões após git pull / clone (aaPanel — usuário www)
set -e

SITE="${1:-/www/wwwroot/lunch.tdesksolutions.com.br}"
WEB_USER="${2:-www}"

if [ ! -d "$SITE/storage" ]; then
  echo "Pasta não encontrada: $SITE"
  exit 1
fi

mkdir -p "$SITE/storage/logs"
chown -R "$WEB_USER:$WEB_USER" "$SITE/storage"
chmod -R 775 "$SITE/storage"

echo "OK: storage/ pertence a $WEB_USER e está gravável (775)."
