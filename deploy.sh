#!/usr/bin/env bash
#
# Deploy do MGspa — substitui build-all.sh + reinicia-fila.sh.
#
# Traz o codigo, atualiza as dependencias, recria os caches da API, builda os
# frontends e reinicia a fila.
#
# Cada etapa pede confirmacao — util quando so um app mudou.
#
# A ordem nao e arbitraria:
#   git pull          -> traz o codigo novo
#   composer install  -> ajusta o vendor pro codigo novo
#   optimize          -> cacheia ja com o codigo e o vendor finais
#   builds            -> frontends
#   queue:restart     -> por ultimo, pros 16 workers subirem lendo o cache novo
#
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# project:service-name (nome do service no docker compose)
PROJECTS=(
  "agro:agro"
  "contas:contas"
  "estoque:estoque"
  "negocios:negocios"
  "notas:notas"
  "pessoas:pessoas"
  "quasar:quasar-mgspa"
)

titulo() {
  echo ""
  echo "=========================================="
  echo ">>> $1"
  echo "=========================================="
}

# Retorna 0 (executa) so com s/sim. Qualquer outra coisa pula.
# Sempre usado dentro de `if`, entao o return 1 nao dispara o `set -e`.
confirma() {
  local resp
  read -r -p ">>> $1  Executar? [s/N] " resp
  case "$resp" in
    [sS]|[sS][iI][mM]) return 0 ;;
    *) echo "    ...pulado." ; return 1 ;;
  esac
}

# ----------------------------------------------------------------- Codigo
titulo "Git — traz o codigo do origin/master"
if confirma "git pull em $ROOT_DIR"; then
  cd "$ROOT_DIR"
  git pull
fi

# ----------------------------------------------------------- Dependencias
titulo "Composer — dependencias do backend"
# Roda como root de proposito: o vendor/ pertence ao root, e como 82:82
# falharia ao escrever. Substitui arquivos com o PHP-FPM no ar, entao
# rode fora do pico — uma requisicao pode pegar o vendor pela metade.
if confirma "composer install"; then
  docker exec mgspa-api composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# ------------------------------------------------------------------- API
titulo "API — caches (config, rotas, views, eventos)"
if confirma "php artisan optimize"; then
  # -u 82:82 = usuario do app (mesmo dos containers scheduler e worker).
  # Sem isso os arquivos de cache ficam com dono root.
  docker exec -u 82:82 mgspa-api php artisan optimize
fi

# ------------------------------------------------------------ Frontends
for entry in "${PROJECTS[@]}"; do
  project="${entry%%:*}"
  service="${entry##*:}"

  titulo "Build: $project  (service: $service)"
  if confirma "npm i && quasar build -m pwa"; then
    cd "$ROOT_DIR/$project"
    docker compose exec -T "$service" \
      sh -c "cd /opt/www/MGspa/$project && npm i && quasar build -m pwa"
    echo ">>> [$project] finalizado."
  fi
done

# ----------------------------------------------------------------- Fila
titulo "Fila — reinicia os workers"
if confirma "queue:restart no mglara e na api"; then
  docker exec mglara-mglara-1 php artisan queue:restart
  # O sinal vai pro Redis e as 16 replicas (api-worker-N) escutam —
  # basta rodar uma vez no container da api.
  docker exec mgspa-api php artisan queue:restart
fi

titulo "Deploy concluído"
