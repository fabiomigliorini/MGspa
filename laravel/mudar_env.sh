#!/bin/bash

# Verifica se o número de argumentos é 3
if [ "$#" -ne 3 ]; then
    echo "Uso: $0 <caminho_do_arquivo> <chave> <valor>"
    exit 1
fi

CAMINHO_ARQUIVO=$1
CHAVE=$2
VALOR=$3

# 1. Valida se o arquivo existe
if [ ! -f "$CAMINHO_ARQUIVO" ]; then
    echo "Erro: O arquivo '$CAMINHO_ARQUIVO' não foi encontrado!"
    exit 1
fi

# 2. Valida se a chave existe no arquivo, ignorando comentários
if ! grep -q -E "^[[:space:]]*#?[[:space:]]*$CHAVE=|^[[:space:]]*#?[[:space:]]*define\('$CHAVE', " "$CAMINHO_ARQUIVO"; then
    echo "Erro: A chave '$CHAVE' não foi encontrada nos formatos esperados."
    exit 1
fi

# 3. Executa as substituições, também ignorando comentários
sed -i -E \
  -e "s/^[[:space:]]*#?[[:space:]]*$CHAVE=.*$/$CHAVE=\"$VALOR\"/" \
  -e "s|^[[:space:]]*#?[[:space:]]*define\('$CHAVE', '.*'\);|define('$CHAVE', '$VALOR');|" \
  "$CAMINHO_ARQUIVO"

echo "O valor da chave '$CHAVE' foi atualizado para '$VALOR' no arquivo '$CAMINHO_ARQUIVO'."
