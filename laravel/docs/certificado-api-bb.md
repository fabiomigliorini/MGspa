# Renovação de Certificado mTLS — API Pix v2 Banco do Brasil

**Empresa:** MIGLIORINI E MIGLIORINI LTDA (CNPJ 04.576.775/0001-60)  
**Aplicação BB:** #9045  
**Certificadora:** AC DIGITALSIGN RFB G3 (cadeia v5 ICP-Brasil)  
**Última atualização:** 31/03/2026  
**Script:** `laravel/docs/extrair-cadeia-bb.sh`

### Validade dos certificados da cadeia atual

| Certificado           | Expira em  | Ação necessária                               |
| --------------------- | ---------- | --------------------------------------------- |
| e-CNPJ A1 (empresa)   | 06/03/2027 | Renovar anualmente e reenviar ao portal BB    |
| AC DIGITALSIGN RFB G3 | 20/02/2029 | Sem ação — só muda se trocar de certificadora |
| AC RFB v4             | 02/03/2029 | Sem ação                                      |
| AC Raiz ICP-Brasil v5 | 02/03/2029 | Sem ação                                      |

---

## Contexto

A API Pix v2 do Banco do Brasil exige autenticação mTLS (mutual TLS). Para isso, é necessário enviar no portal Developers BB a cadeia completa de certificados: raiz, intermediários e o certificado da empresa (e-CNPJ A1).

Quando o certificado A1 for renovado (anualmente), é preciso repetir este processo.

---

## Passo 1 — Extrair o certificado da empresa a partir do PFX

O certificado A1 renovado virá como arquivo `.pfx`. Extrair somente o certificado público (sem chave privada):

```bash
openssl pkcs12 -in "CERTIFICADO.pfx" -clcerts -nokeys -out empresa.pem
```

Verificar quem emitiu (o issuer indica qual AC intermediária foi usada):

```bash
openssl x509 -in empresa.pem -noout -issuer -subject -dates
```

Se o issuer continuar sendo `AC DIGITALSIGN RFB G3`, os mesmos certificados intermediários abaixo servem. Se mudar de AC, ajustar os downloads conforme a nova cadeia.

---

## Passo 2 — Baixar a cadeia de certificados da ICP-Brasil

A hierarquia atual (cadeia v5) é:

```
AC Raiz ICP-Brasil v5 (raiz)
  └── AC Secretaria da Receita Federal do Brasil v4 (1º nível - intermediário)
        └── AC DIGITALSIGN RFB G3 (2º nível - intermediário)
              └── Certificado da empresa (folha)
```

### 2.1 — Certificado Raiz (AC Raiz ICP-Brasil v5)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/repositorio-ac-raiz

```bash
curl -o 1_Raiz_ICP-Brasil_v5.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RAIZ/ICP-Brasilv5.crt
```

### 2.2 — Certificado Intermediário A (AC RFB v4)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-secretaria-da-receita-federal-do-brasil-de-1deg-nivel

```bash
curl -o 2_Intermediario_AC_RFB_v4.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/p/AC_Secretaria_da_Receita_Federal_do_Brasil_v4.crt
```

### 2.3 — Certificado Intermediário B (AC DIGITALSIGN RFB G3)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-digitalsign-rfb-de-2o-nivel

Na cadeia v5, são listados dois certificados (G2 e G3). Baixar o **G3** (emitido em 26/07/2019):

```bash
curl -o 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/AC_DIGITALSIGN_RFB_G3.crt
```

⚠️ **ATENÇÃO:** Não confundir com o G2! Verificar com:

```bash
openssl x509 -in 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt -inform DER -noout -subject
# Deve mostrar: CN = AC DIGITALSIGN RFB G3
```

---

## Passo 3 — Verificar a cadeia antes de enviar

Confirmar que todos os certificados estão corretos e a cadeia fecha:

```bash
# Ver detalhes de cada certificado (formato DER)
for cert in 1_Raiz_ICP-Brasil_v5.crt 2_Intermediario_AC_RFB_v4.crt 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt; do
  echo "=== $cert ==="
  openssl x509 -in "$cert" -inform DER -noout -subject -issuer -dates
  echo ""
done

# Ver o certificado da empresa (formato PEM)
echo "=== empresa.pem ==="
openssl x509 -in empresa.pem -noout -subject -issuer -dates
```

A cadeia deve fechar assim:

| Certificado     | Subject (CN)                                  | Issuer (CN)                                   |
| --------------- | --------------------------------------------- | --------------------------------------------- |
| Raiz            | AC Raiz ICP-Brasil v5                         | AC Raiz ICP-Brasil v5 (autoassinado)          |
| Intermediário A | AC Secretaria da Receita Federal do Brasil v4 | AC Raiz ICP-Brasil v5                         |
| Intermediário B | AC DIGITALSIGN RFB G3                         | AC Secretaria da Receita Federal do Brasil v4 |
| Empresa         | MIGLIORINI E MIGLIORINI LTDA:04576775000160   | AC DIGITALSIGN RFB G3                         |

---

## Passo 4 — Enviar no Portal Developers BB

1. Acessar https://app.developers.bb.com.br
2. Ir em **Minhas Aplicações** → selecionar a aplicação (#9045)
3. Clicar em **Certificados** → **ENVIAR CERTIFICADO**
4. Ambiente: **Produção**
5. Preencher os campos:

| Campo no portal                                                                  | Arquivo                                     |
| -------------------------------------------------------------------------------- | ------------------------------------------- |
| Certificado Raiz                                                                 | `1_Raiz_ICP-Brasil_v5.crt`                  |
| Certificado Intermediário (1º)                                                   | `2_Intermediario_AC_RFB_v4.crt`             |
| Certificado Intermediário (2º) — clicar em "Adicionar certificado intermediário" | `3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt` |
| Certificado Empresa                                                              | `empresa.pem`                               |

6. Clicar em **ENVIAR**
7. Aguardar o status mudar para **"Atendido"** (até 3 dias úteis)

---

## Script automatizado — `extrair-cadeia-bb.sh`

O script `extrair-cadeia-bb.sh` (localizado em `laravel/docs/`) automatiza todo o processo: extrai certificado e chave do PFX, baixa a cadeia ICP-Brasil, converte formatos, monta o arquivo de cadeia completa e valida.

### Uso

```bash
./extrair-cadeia-bb.sh "Migliorini - Mari - 2026.pfx"
```

Vai pedir a senha do PFX duas vezes (certificado + chave privada). Todos os arquivos são gerados em `/tmp/certs/`:

| Arquivo                         | Finalidade                                              |
| ------------------------------- | ------------------------------------------------------- |
| `cadeia_completa.pem`           | Para o botão "Importar cadeia completa" no portal BB    |
| `raiz.pem`                      | Certificado Raiz (upload individual)                    |
| `intermediario_rfb.pem`         | Intermediário 1 — AC RFB v4 (upload individual)         |
| `intermediario_digitalsign.pem` | Intermediário 2 — AC DIGITALSIGN G3 (upload individual) |
| `empresa.pem`                   | Certificado da empresa (upload individual)              |
| `chave.key`                     | Chave privada para chamadas mTLS via PEM+KEY            |

O script valida a cadeia completa ao final. Se aparecer `CADEIA VÁLIDA!`, pode enviar ao portal BB com segurança.

---

## Quando o certificado for renovado ou a certificadora mudar

### Renovação com a mesma certificadora (AC DIGITALSIGN RFB G3)

Basta rodar o script com o novo PFX. Os certificados raiz e intermediários da cadeia v5 continuam válidos até 2029 — o script baixa os mesmos arquivos.

### Troca de certificadora (ex: de DIGITALSIGN para CERTISIGN, VALID, SERASA etc.)

Se ao renovar o e-CNPJ a empresa trocar de certificadora, a cadeia muda e o script precisa ser ajustado. Para investigar:

1. **Identificar o novo emissor:**

   ```bash
   openssl pkcs12 -in "NOVO.pfx" -clcerts -nokeys | openssl x509 -noout -issuer
   ```

   O `CN` do issuer mostra a nova AC intermediária (ex: `AC CERTISIGN RFB G5`).

2. **Descobrir a hierarquia da nova AC:**
   - Acessar https://www.gov.br/iti/pt-br/assuntos/repositorio/cadeias-da-icp-brasil
   - Navegar em **AC Receita Federal do Brasil** → localizar a AC de 2º nível correspondente
   - Verificar em qual cadeia ela está (v5, v12 etc.)

3. **Baixar os novos certificados intermediários:**
   - A AC de 2º nível (nova certificadora) está no repositório do ITI
   - A AC de 1º nível (AC RFB) pode mudar de versão dependendo da cadeia
   - A AC Raiz muda se a cadeia for diferente (ex: v12 em vez de v5)

4. **Atualizar as URLs no script:**
   - As URLs de download seguem o padrão `https://acraiz.icpbrasil.gov.br/credenciadas/...`
   - Ajustar os 3 curls no script para apontar para os novos certificados
   - O path pode variar (algumas ACs usam `/p/` no caminho, outras não)

5. **Validar antes de enviar:**
   ```bash
   openssl verify -CAfile raiz.pem -untrusted intermediario1.pem -untrusted intermediario2.pem empresa.pem
   ```
   Se retornar `OK`, a cadeia está correta.

### Checklist rápido de diagnóstico

| Problema                                      | Causa provável                                              | O que verificar                                                      |
| --------------------------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------- |
| "Certificado inválido" no portal BB           | Certificado intermediário errado (ex: G2 em vez de G3)      | `openssl x509 -in intermediario.crt -noout -subject` — conferir o CN |
| Cadeia não valida (`verify` falha)            | Cadeia misturada (raiz v5 + intermediário de cadeia v12)    | Todos os certificados devem ser da mesma cadeia                      |
| Download retorna arquivo pequeno (<500 bytes) | URL errada ou redirect                                      | `cat arquivo.crt` — se vier HTML, a URL está errada                  |
| curl falha com SSL error                      | Site ICP-Brasil usa certificado não reconhecido pelo Ubuntu | Adicionar flag `-k` ao curl                                          |
| Script para no meio sem mensagem              | `set -e` + conversão DER/PEM falhou                         | O script já trata ambos os formatos automaticamente                  |

---

## Uso no Laravel (Guzzle/Http)

### Opção A — Usando o PFX diretamente

```php
Http::withOptions([
    'cert' => [storage_path('certs/certificado.pfx'), 'SENHA_DO_PFX'],
    'curl' => [
        CURLOPT_SSLCERTTYPE => 'P12',
    ],
])->post('https://api-pix.bb.com.br/...');
```

### Opção B — Usando PEM + KEY separados

Extrair a chave privada do PFX:

```bash
openssl pkcs12 -in "CERTIFICADO.pfx" -nocerts -out chave.key
# (vai pedir senha do PFX e senha para proteger a chave)
```

```php
Http::withOptions([
    'cert' => storage_path('certs/empresa.pem'),
    'ssl_key' => [storage_path('certs/chave.key'), 'SENHA_DA_CHAVE'],
])->post('https://api-pix.bb.com.br/...');
```

---

## URLs de referência

| Recurso                                 | URL                                                                                                        |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| Portal Developers BB                    | https://app.developers.bb.com.br                                                                           |
| Documentação API Pix v2 BB              | https://apoio.developers.bb.com.br                                                                         |
| Repositório AC Raiz ICP-Brasil          | https://www.gov.br/iti/pt-br/assuntos/repositorio/repositorio-ac-raiz                                      |
| Repositório AC RFB 1º nível             | https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-secretaria-da-receita-federal-do-brasil-de-1deg-nivel |
| Repositório AC DIGITALSIGN RFB 2º nível | https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-digitalsign-rfb-de-2o-nivel                           |
| BaseURL API Pix v2 Produção             | `https://api-pix.bb.com.br`                                                                                |
| BaseURL API Pix v2 Homologação          | `https://api-pix.hm.bb.com.br`                                                                             | # Renovação de Certificado mTLS — API Pix v2 Banco do Brasil |

**Empresa:** MIGLIORINI E MIGLIORINI LTDA (CNPJ 04.576.775/0001-60)  
**Aplicação BB:** #9045  
**Certificadora:** AC DIGITALSIGN RFB G3 (cadeia v5 ICP-Brasil)  
**Última atualização:** 31/03/2026  
**Script:** `laravel/docs/extrair-cadeia-bb.sh`

### Validade dos certificados da cadeia atual

| Certificado           | Expira em  | Ação necessária                               |
| --------------------- | ---------- | --------------------------------------------- |
| e-CNPJ A1 (empresa)   | 06/03/2027 | Renovar anualmente e reenviar ao portal BB    |
| AC DIGITALSIGN RFB G3 | 20/02/2029 | Sem ação — só muda se trocar de certificadora |
| AC RFB v4             | 02/03/2029 | Sem ação                                      |
| AC Raiz ICP-Brasil v5 | 02/03/2029 | Sem ação                                      |

---

## Contexto

A API Pix v2 do Banco do Brasil exige autenticação mTLS (mutual TLS). Para isso, é necessário enviar no portal Developers BB a cadeia completa de certificados: raiz, intermediários e o certificado da empresa (e-CNPJ A1).

Quando o certificado A1 for renovado (anualmente), é preciso repetir este processo.

---

## Passo 1 — Extrair o certificado da empresa a partir do PFX

O certificado A1 renovado virá como arquivo `.pfx`. Extrair somente o certificado público (sem chave privada):

```bash
openssl pkcs12 -in "CERTIFICADO.pfx" -clcerts -nokeys -out empresa.pem
```

Verificar quem emitiu (o issuer indica qual AC intermediária foi usada):

```bash
openssl x509 -in empresa.pem -noout -issuer -subject -dates
```

Se o issuer continuar sendo `AC DIGITALSIGN RFB G3`, os mesmos certificados intermediários abaixo servem. Se mudar de AC, ajustar os downloads conforme a nova cadeia.

---

## Passo 2 — Baixar a cadeia de certificados da ICP-Brasil

A hierarquia atual (cadeia v5) é:

```
AC Raiz ICP-Brasil v5 (raiz)
  └── AC Secretaria da Receita Federal do Brasil v4 (1º nível - intermediário)
        └── AC DIGITALSIGN RFB G3 (2º nível - intermediário)
              └── Certificado da empresa (folha)
```

### 2.1 — Certificado Raiz (AC Raiz ICP-Brasil v5)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/repositorio-ac-raiz

```bash
curl -o 1_Raiz_ICP-Brasil_v5.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RAIZ/ICP-Brasilv5.crt
```

### 2.2 — Certificado Intermediário A (AC RFB v4)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-secretaria-da-receita-federal-do-brasil-de-1deg-nivel

```bash
curl -o 2_Intermediario_AC_RFB_v4.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/p/AC_Secretaria_da_Receita_Federal_do_Brasil_v4.crt
```

### 2.3 — Certificado Intermediário B (AC DIGITALSIGN RFB G3)

**Página:** https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-digitalsign-rfb-de-2o-nivel

Na cadeia v5, são listados dois certificados (G2 e G3). Baixar o **G3** (emitido em 26/07/2019):

```bash
curl -o 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt \
  https://acraiz.icpbrasil.gov.br/credenciadas/RFB/v5/AC_DIGITALSIGN_RFB_G3.crt
```

⚠️ **ATENÇÃO:** Não confundir com o G2! Verificar com:

```bash
openssl x509 -in 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt -inform DER -noout -subject
# Deve mostrar: CN = AC DIGITALSIGN RFB G3
```

---

## Passo 3 — Verificar a cadeia antes de enviar

Confirmar que todos os certificados estão corretos e a cadeia fecha:

```bash
# Ver detalhes de cada certificado (formato DER)
for cert in 1_Raiz_ICP-Brasil_v5.crt 2_Intermediario_AC_RFB_v4.crt 3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt; do
  echo "=== $cert ==="
  openssl x509 -in "$cert" -inform DER -noout -subject -issuer -dates
  echo ""
done

# Ver o certificado da empresa (formato PEM)
echo "=== empresa.pem ==="
openssl x509 -in empresa.pem -noout -subject -issuer -dates
```

A cadeia deve fechar assim:

| Certificado     | Subject (CN)                                  | Issuer (CN)                                   |
| --------------- | --------------------------------------------- | --------------------------------------------- |
| Raiz            | AC Raiz ICP-Brasil v5                         | AC Raiz ICP-Brasil v5 (autoassinado)          |
| Intermediário A | AC Secretaria da Receita Federal do Brasil v4 | AC Raiz ICP-Brasil v5                         |
| Intermediário B | AC DIGITALSIGN RFB G3                         | AC Secretaria da Receita Federal do Brasil v4 |
| Empresa         | MIGLIORINI E MIGLIORINI LTDA:04576775000160   | AC DIGITALSIGN RFB G3                         |

---

## Passo 4 — Enviar no Portal Developers BB

1. Acessar https://app.developers.bb.com.br
2. Ir em **Minhas Aplicações** → selecionar a aplicação (#9045)
3. Clicar em **Certificados** → **ENVIAR CERTIFICADO**
4. Ambiente: **Produção**
5. Preencher os campos:

| Campo no portal                                                                  | Arquivo                                     |
| -------------------------------------------------------------------------------- | ------------------------------------------- |
| Certificado Raiz                                                                 | `1_Raiz_ICP-Brasil_v5.crt`                  |
| Certificado Intermediário (1º)                                                   | `2_Intermediario_AC_RFB_v4.crt`             |
| Certificado Intermediário (2º) — clicar em "Adicionar certificado intermediário" | `3_Intermediario_AC_DIGITALSIGN_RFB_G3.crt` |
| Certificado Empresa                                                              | `empresa.pem`                               |

6. Clicar em **ENVIAR**
7. Aguardar o status mudar para **"Atendido"** (até 3 dias úteis)

---

## Script automatizado — `extrair-cadeia-bb.sh`

O script `extrair-cadeia-bb.sh` (localizado em `laravel/docs/`) automatiza todo o processo: extrai certificado e chave do PFX, baixa a cadeia ICP-Brasil, converte formatos, monta o arquivo de cadeia completa e valida.

### Uso

```bash
./extrair-cadeia-bb.sh "Migliorini - Mari - 2026.pfx"
```

Vai pedir a senha do PFX duas vezes (certificado + chave privada). Todos os arquivos são gerados em `/tmp/certs/`:

| Arquivo                         | Finalidade                                              |
| ------------------------------- | ------------------------------------------------------- |
| `cadeia_completa.pem`           | Para o botão "Importar cadeia completa" no portal BB    |
| `raiz.pem`                      | Certificado Raiz (upload individual)                    |
| `intermediario_rfb.pem`         | Intermediário 1 — AC RFB v4 (upload individual)         |
| `intermediario_digitalsign.pem` | Intermediário 2 — AC DIGITALSIGN G3 (upload individual) |
| `empresa.pem`                   | Certificado da empresa (upload individual)              |
| `chave.key`                     | Chave privada para chamadas mTLS via PEM+KEY            |

O script valida a cadeia completa ao final. Se aparecer `CADEIA VÁLIDA!`, pode enviar ao portal BB com segurança.

---

## Quando o certificado for renovado ou a certificadora mudar

### Renovação com a mesma certificadora (AC DIGITALSIGN RFB G3)

Basta rodar o script com o novo PFX. Os certificados raiz e intermediários da cadeia v5 continuam válidos até 2029 — o script baixa os mesmos arquivos.

### Troca de certificadora (ex: de DIGITALSIGN para CERTISIGN, VALID, SERASA etc.)

Se ao renovar o e-CNPJ a empresa trocar de certificadora, a cadeia muda e o script precisa ser ajustado. Para investigar:

1. **Identificar o novo emissor:**

   ```bash
   openssl pkcs12 -in "NOVO.pfx" -clcerts -nokeys | openssl x509 -noout -issuer
   ```

   O `CN` do issuer mostra a nova AC intermediária (ex: `AC CERTISIGN RFB G5`).

2. **Descobrir a hierarquia da nova AC:**
   - Acessar https://www.gov.br/iti/pt-br/assuntos/repositorio/cadeias-da-icp-brasil
   - Navegar em **AC Receita Federal do Brasil** → localizar a AC de 2º nível correspondente
   - Verificar em qual cadeia ela está (v5, v12 etc.)

3. **Baixar os novos certificados intermediários:**
   - A AC de 2º nível (nova certificadora) está no repositório do ITI
   - A AC de 1º nível (AC RFB) pode mudar de versão dependendo da cadeia
   - A AC Raiz muda se a cadeia for diferente (ex: v12 em vez de v5)

4. **Atualizar as URLs no script:**
   - As URLs de download seguem o padrão `https://acraiz.icpbrasil.gov.br/credenciadas/...`
   - Ajustar os 3 curls no script para apontar para os novos certificados
   - O path pode variar (algumas ACs usam `/p/` no caminho, outras não)

5. **Validar antes de enviar:**
   ```bash
   openssl verify -CAfile raiz.pem -untrusted intermediario1.pem -untrusted intermediario2.pem empresa.pem
   ```
   Se retornar `OK`, a cadeia está correta.

### Checklist rápido de diagnóstico

| Problema                                      | Causa provável                                              | O que verificar                                                      |
| --------------------------------------------- | ----------------------------------------------------------- | -------------------------------------------------------------------- |
| "Certificado inválido" no portal BB           | Certificado intermediário errado (ex: G2 em vez de G3)      | `openssl x509 -in intermediario.crt -noout -subject` — conferir o CN |
| Cadeia não valida (`verify` falha)            | Cadeia misturada (raiz v5 + intermediário de cadeia v12)    | Todos os certificados devem ser da mesma cadeia                      |
| Download retorna arquivo pequeno (<500 bytes) | URL errada ou redirect                                      | `cat arquivo.crt` — se vier HTML, a URL está errada                  |
| curl falha com SSL error                      | Site ICP-Brasil usa certificado não reconhecido pelo Ubuntu | Adicionar flag `-k` ao curl                                          |
| Script para no meio sem mensagem              | `set -e` + conversão DER/PEM falhou                         | O script já trata ambos os formatos automaticamente                  |

---

## Uso no Laravel (Guzzle/Http)

### Opção A — Usando o PFX diretamente

```php
Http::withOptions([
    'cert' => [storage_path('certs/certificado.pfx'), 'SENHA_DO_PFX'],
    'curl' => [
        CURLOPT_SSLCERTTYPE => 'P12',
    ],
])->post('https://api-pix.bb.com.br/...');
```

### Opção B — Usando PEM + KEY separados

Extrair a chave privada do PFX:

```bash
openssl pkcs12 -in "CERTIFICADO.pfx" -nocerts -out chave.key
# (vai pedir senha do PFX e senha para proteger a chave)
```

```php
Http::withOptions([
    'cert' => storage_path('certs/empresa.pem'),
    'ssl_key' => [storage_path('certs/chave.key'), 'SENHA_DA_CHAVE'],
])->post('https://api-pix.bb.com.br/...');
```

---

## URLs de referência

| Recurso                                 | URL                                                                                                        |
| --------------------------------------- | ---------------------------------------------------------------------------------------------------------- |
| Portal Developers BB                    | https://app.developers.bb.com.br                                                                           |
| Documentação API Pix v2 BB              | https://apoio.developers.bb.com.br                                                                         |
| Repositório AC Raiz ICP-Brasil          | https://www.gov.br/iti/pt-br/assuntos/repositorio/repositorio-ac-raiz                                      |
| Repositório AC RFB 1º nível             | https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-secretaria-da-receita-federal-do-brasil-de-1deg-nivel |
| Repositório AC DIGITALSIGN RFB 2º nível | https://www.gov.br/iti/pt-br/assuntos/repositorio/ac-digitalsign-rfb-de-2o-nivel                           |
| BaseURL API Pix v2 Produção             | `https://api-pix.bb.com.br`                                                                                |
| BaseURL API Pix v2 Homologação          | `https://api-pix.hm.bb.com.br`                                                                             |
