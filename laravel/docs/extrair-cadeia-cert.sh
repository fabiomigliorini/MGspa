#!/bin/bash
#
# extrair-cadeia-bb.sh
# Extrai a cadeia completa de certificados de um PFX (e-CNPJ ICP-Brasil)
# para envio no portal developers do Banco do Brasil (API Pix v2 mTLS)
#
# Uso: ./extrair-cadeia-bb.sh certificado.pfx
#

set -e

# ---------------------------------------------------------------------------
# Validações
# ---------------------------------------------------------------------------
if [ -z "$1" ]; then
    echo "Uso: $0 <arquivo.pfx>"
    echo "Exemplo: $0 'Migliorini - Mari - 2026.pfx'"
    exit 1
fi

PFX="$(realpath "$1")"

if [ ! -f "$PFX" ]; then
    echo "ERRO: Arquivo não encontrado: $PFX"
    exit 1
fi

# ---------------------------------------------------------------------------
# Preparar diretório de trabalho
# ---------------------------------------------------------------------------
WORKDIR="/tmp/certs"
rm -rf "$WORKDIR"
mkdir -p "$WORKDIR"
cd "$WORKDIR"

echo "============================================="
echo " Extração de cadeia de certificados para BB"
echo "============================================="
echo ""
echo "PFX: $PFX"
echo "Diretório de trabalho: $WORKDIR"
echo ""

# ---------------------------------------------------------------------------
# 1. Extrair certificado da empresa (folha) do PFX
# ---------------------------------------------------------------------------
echo "[1/6] Extraindo certificado da empresa do PFX..."
openssl pkcs12 -in "$PFX" -clcerts -nokeys -out empresa.pem 2>/dev/null
echo "      -> empresa.pem"

# ---------------------------------------------------------------------------
# 2. Extrair chave privada do PFX (necessária para as chamadas mTLS)
# ---------------------------------------------------------------------------
echo "[2/6] Extraindo chave privada do PFX..."
openssl pkcs12 -in "$PFX" -nocerts -nodes -out chave.key 2>/dev/null
echo "      -> chave.key"

# ---------------------------------------------------------------------------
# 3. Identificar o emissor do certificado
# ---------------------------------------------------------------------------
echo "[3/6] Identificando cadeia do certificado..."
ISSUER=$(openssl x509 -in empresa.pem -noout -issuer)
echo "      Emissor: $ISSUER"

# ---------------------------------------------------------------------------
# 4. Baixar certificados da cadeia ICP-Brasil
# ---------------------------------------------------------------------------
echo "[4/6] Baixando certificados da cadeia ICP-Brasil..."

# AC Raiz ICP-Brasil v5
echo "      -> AC Raiz ICP-Brasil v5..."
curl -sSk -o raiz_v5.crt \
    "https://acraiz.icpbrasil.gov.br/credenciadas/RAIZ/ICP-Brasilv5.crt"

# AC Secretaria da Receita Federal do Brasil v4 (1º nível, cadeia v5)
echo "      -> AC Secretaria da Receita Federal do Brasil v4..."
curl -sSk -o intermediario_rfb_v4.crt \
    "http://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/p/AC_Secretaria_da_Receita_Federal_do_Brasil_v4.crt"

# AC DIGITALSIGN RFB G3 (2º nível, cadeia v5)
echo "      -> AC DIGITALSIGN RFB G3..."
curl -sSk -o intermediario_digitalsign_g3.crt \
    "https://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/AC_DIGITALSIGN_RFB_G3.crt"

# ---------------------------------------------------------------------------
# 5. Converter DER para PEM
# ---------------------------------------------------------------------------
echo "[5/6] Convertendo certificados para PEM..."

converter_para_pem() {
    local entrada="$1"
    local saida="$2"
    # Tenta DER primeiro, se falhar assume PEM
    if openssl x509 -inform DER -in "$entrada" -out "$saida" 2>/dev/null; then
        echo "      -> $saida (convertido de DER)"
    elif openssl x509 -inform PEM -in "$entrada" -out "$saida" 2>/dev/null; then
        echo "      -> $saida (já era PEM)"
    else
        echo "      ERRO: não foi possível converter $entrada"
        exit 1
    fi
}

converter_para_pem raiz_v5.crt raiz.pem
converter_para_pem intermediario_rfb_v4.crt intermediario_rfb.pem
converter_para_pem intermediario_digitalsign_g3.crt intermediario_digitalsign.pem

# ---------------------------------------------------------------------------
# 6. Montar cadeia completa (para importação no portal BB)
# ---------------------------------------------------------------------------
echo "[6/6] Montando cadeia completa..."

cat raiz.pem intermediario_rfb.pem intermediario_digitalsign.pem empresa.pem > cadeia_completa.pem
echo "      -> cadeia_completa.pem"

# ---------------------------------------------------------------------------
# Verificar a cadeia
# ---------------------------------------------------------------------------
echo ""
echo "============================================="
echo " Verificação da cadeia"
echo "============================================="

echo ""
echo "--- Certificado Raiz ---"
openssl x509 -in raiz.pem -noout -subject -dates

echo ""
echo "--- Intermediário 1 (AC RFB v4) ---"
openssl x509 -in intermediario_rfb.pem -noout -subject -dates

echo ""
echo "--- Intermediário 2 (AC DIGITALSIGN RFB G3) ---"
openssl x509 -in intermediario_digitalsign.pem -noout -subject -dates

echo ""
echo "--- Certificado Empresa ---"
openssl x509 -in empresa.pem -noout -subject -dates

echo ""
echo "--- Validando cadeia completa ---"
if openssl verify -CAfile raiz.pem -untrusted intermediario_rfb.pem -untrusted intermediario_digitalsign.pem empresa.pem 2>/dev/null; then
    echo "CADEIA VÁLIDA!"
else
    echo "ATENÇÃO: Falha na validação da cadeia. Verifique se o certificado pertence à cadeia v5 / AC DIGITALSIGN RFB G3."
fi

# ---------------------------------------------------------------------------
# Resumo final
# ---------------------------------------------------------------------------
echo ""
echo "============================================="
echo " Arquivos gerados em $WORKDIR"
echo "============================================="
echo ""
echo " Para o portal BB (botão 'Importar cadeia completa'):"
echo "   -> cadeia_completa.pem"
echo ""
echo " Para upload individual no portal BB:"
echo "   Certificado Raiz        -> raiz.pem"
echo "   Intermediário 1 (RFB)   -> intermediario_rfb.pem"
echo "   Intermediário 2 (DSGN)  -> intermediario_digitalsign.pem"
echo "   Certificado Empresa     -> empresa.pem"
echo ""
echo " Para uso nas chamadas mTLS da API Pix:"
echo "   Opção 1 (PFX direto):   $PFX"
echo "   Opção 2 (PEM + KEY):    empresa.pem + chave.key"
echo ""