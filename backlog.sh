#!/usr/bin/env bash
#
# Backlog.md sem instalar nada.
#
# Os containers do projeto sao Alpine (musl) e o binario do Backlog.md e' linkado
# com glibc, entao nao da pra rodar dentro deles. A solucao e' um container
# DESCARTAVEL (docker run --rm): nasce, roda o comando e morre. Nenhum container
# do projeto e' tocado e nada e' instalado no host.
#
# Uso:  ./backlog.sh task list -s "To Do"
#       ./backlog.sh board
# Atalho sugerido no seu shell:
#       alias backlog=/opt/www/MGspa/backlog.sh
#
set -euo pipefail

BACKLOG_VERSION="${BACKLOG_VERSION:-1.52.0}"
REPO="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Cache do npx: diretorio do proprio usuario, criado aqui para nascer com o dono
# certo. Volume nomeado do docker nao serve: nasce root-owned e quebra o npm.
CACHE="${XDG_CACHE_HOME:-$HOME/.cache}/backlog-npx"
mkdir -p "$CACHE"

# -t so quando tem terminal, senao quebra em pipe/CI (ex: | wc -l)
TTY_FLAG=()
[ -t 0 ] && [ -t 1 ] && TTY_FLAG=(-t)

exec docker run --rm -i "${TTY_FLAG[@]}" \
  -u "$(id -u):$(id -g)" \
  -v "$REPO":"$REPO" \
  -v "$CACHE":/cache \
  -e npm_config_cache=/cache \
  -e HOME=/cache \
  -w "$REPO" \
  node:22-slim \
  npx -y "backlog.md@${BACKLOG_VERSION}" "$@"
