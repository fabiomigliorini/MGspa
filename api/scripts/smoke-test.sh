#!/bin/bash
# Smoke test em massa: itera GETs da api e classifica status code.
# Token via scripts/smoke-token.php (Personal Access Token Passport).
#
# Uso: ./scripts/smoke-test.sh [base-url] [out-file]
# Defaults: base-url=https://api-dev.mgpapelaria.com.br, out=/tmp/smoke-$(date +%s).csv

set -u

BASE="${1:-https://api-dev.mgpapelaria.com.br}"
OUT="${2:-/tmp/smoke-$(date +%s).csv}"

echo ">> Gerando token Passport..."
TOKEN=$(docker exec mgspa-api php /opt/www/MGspa/api/scripts/smoke-token.php | tail -1)
if [ -z "$TOKEN" ]; then
    echo "ERRO: não obteve token" >&2
    exit 1
fi

echo ">> Listando rotas v1 GET sem parâmetro..."
URIS=$(docker exec mgspa-api php artisan route:list --json 2>/dev/null \
    | jq -r '.[]
        | select(.method | contains("GET"))
        | select(.uri | startswith("api/v1/"))
        | select(.uri | test("\\{") | not)
        | .uri' \
    | sort -u)

TOTAL=$(echo "$URIS" | wc -l)
echo ">> $TOTAL rotas pra testar"
echo "status,uri,size_bytes" > "$OUT"

I=0
echo "$URIS" | while read uri; do
    I=$((I+1))
    URL="$BASE/$uri"
    # -o /dev/null = não imprime body / -w = formato status,size / -s silent
    RESULT=$(curl -sk -o /tmp/last.body -w "%{http_code},%{size_download}" \
        -H "Authorization: Bearer $TOKEN" \
        -H "Accept: application/json" \
        --max-time 30 \
        "$URL")
    CODE=$(echo "$RESULT" | cut -d',' -f1)
    SIZE=$(echo "$RESULT" | cut -d',' -f2)
    echo "$CODE,$uri,$SIZE" >> "$OUT"
    # Marcador visual: progresso 5xx em vermelho, 4xx em amarelo, 2xx pontinho
    if [ "$CODE" -ge 500 ] 2>/dev/null; then
        echo "  [$I/$TOTAL] $CODE $uri  <- 5XX"
    elif [ "$CODE" -ge 400 ] 2>/dev/null; then
        echo "  [$I/$TOTAL] $CODE $uri"
    fi
done

echo ""
echo "=== RESUMO ==="
awk -F',' 'NR>1 { c[substr($1,1,1)"xx"]++ } END {for (k in c) print c[k], k}' "$OUT" | sort
echo ""
echo "=== 5XX (regressões) ==="
awk -F',' 'NR>1 && $1>=500' "$OUT" | sort -u
echo ""
echo "=== 4XX (esperados ou não?) ==="
awk -F',' 'NR>1 && $1>=400 && $1<500' "$OUT" | sort -u | head -30
echo ""
echo ">> Output completo em $OUT"
