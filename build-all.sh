#!/usr/bin/env bash
set -e

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# project:service-name (docker compose service)
PROJECTS=(
  "agro:agro"
  "contas:contas"
  "estoque:estoque"
  "negocios:negocios"
  "notas:notas"
  "pessoas:pessoas"
  "quasar:quasar-mgspa"
)

for entry in "${PROJECTS[@]}"; do
  project="${entry%%:*}"
  service="${entry##*:}"
  dir="$ROOT_DIR/$project"

  echo ""
  echo "=========================================="
  echo ">>> Building: $project  (service: $service)"
  echo "=========================================="

  cd "$dir"
  docker compose exec -T "$service" sh -c "cd /opt/www/MGspa/$project && npm i && quasar build -m pwa"

  echo ""
  read -n 1 -s -r -p ">>> [$project] finalizado. Pressione qualquer tecla para continuar..."
  echo ""
done

echo ""
echo "=========================================="
echo ">>> All builds completed successfully"
echo "=========================================="
