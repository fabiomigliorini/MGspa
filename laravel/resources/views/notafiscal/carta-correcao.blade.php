<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 1.5cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #000;
        }

        .header-box {
            border: 2px solid #000;
            padding: 15px;
            text-align: center;
            margin-bottom: 20px;
        }

        .header-title {
            font-size: 16pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-size: 10pt;
            color: #333;
            margin-bottom: 10px;
        }

        .header-note {
            font-size: 8pt;
            color: #666;
            font-style: italic;
        }

        .section {
            margin-bottom: 15px;
            border: 1px solid #ccc;
            padding: 10px;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 5px 10px;
            margin: -10px -10px 10px -10px;
            border-bottom: 1px solid #ccc;
        }

        .field {
            margin-bottom: 5px;
        }

        .field-label {
            font-weight: bold;
            display: inline;
        }

        .field-value {
            display: inline;
        }

        .chave-acesso {
            font-family: 'Courier New', monospace;
            font-size: 11pt;
            letter-spacing: 1px;
            background-color: #f9f9f9;
            padding: 8px;
            border: 1px dashed #999;
            text-align: center;
            margin: 10px 0;
            word-break: break-all;
        }

        .texto-correcao {
            background-color: #fffef0;
            border: 1px solid #e6e6b8;
            padding: 15px;
            margin: 10px 0;
            white-space: pre-wrap;
            font-size: 10pt;
        }

        .observacoes {
            background-color: #fff5f5;
            border: 1px solid #ffcccc;
            padding: 10px;
        }

        .observacoes ul {
            margin: 5px 0;
            padding-left: 20px;
        }

        .observacoes li {
            margin-bottom: 3px;
        }

        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
            text-align: center;
            color: #666;
        }

        .sem-valor-fiscal {
            font-size: 12pt;
            font-weight: bold;
            color: #cc0000;
            text-align: center;
            padding: 10px;
            border: 2px solid #cc0000;
            margin: 15px 0;
        }

        table.dados {
            width: 100%;
            border-collapse: collapse;
        }

        table.dados td {
            padding: 3px 0;
            vertical-align: top;
        }

        table.dados td.label {
            width: 180px;
            font-weight: bold;
        }
    </style>
</head>

<body>

    <!-- CABECALHO -->
    <div class="header-box">
        <div class="header-title">CARTA DE CORRECAO ELETRONICA</div>
        <div class="header-subtitle">(DOCUMENTO AUXILIAR - SEM VALOR FISCAL)</div>
        <div class="header-note">
            Este documento e apenas um espelho da CC-e registrada na NF-e.<br>
            A validade juridica esta no XML autorizado pela SEFAZ.
        </div>
    </div>

    <div class="sem-valor-fiscal">
        DOCUMENTO AUXILIAR - SEM VALOR FISCAL
    </div>

    <!-- IDENTIFICACAO DO EMITENTE -->
    <div class="section">
        <div class="section-title">IDENTIFICACAO DO EMITENTE</div>
        <table class="dados">
            <tr>
                <td class="label">Razao Social:</td>
                <td>{{ $filial->Pessoa->pessoa }}</td>
            </tr>
            <tr>
                <td class="label">CNPJ:</td>
                <td>{{ formataCnpj($filial->Pessoa->cnpj) }}</td>
            </tr>
            @if(!empty($filial->Pessoa->ie))
            <tr>
                <td class="label">Inscricao Estadual:</td>
                <td>{{ $filial->Pessoa->ie }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Endereco:</td>
                <td>
                    {{ $filial->Pessoa->endereco }}, {{ $filial->Pessoa->numero }}
                    @if(!empty($filial->Pessoa->complemento))
                        - {{ $filial->Pessoa->complemento }}
                    @endif
                    - {{ $filial->Pessoa->bairro }}
                    - {{ $filial->Pessoa->Cidade->cidade }}/{{ $filial->Pessoa->Cidade->Estado->sigla }}
                </td>
            </tr>
        </table>
    </div>

    <!-- IDENTIFICACAO DA NF-e CORRIGIDA -->
    <div class="section">
        <div class="section-title">IDENTIFICACAO DA NF-e CORRIGIDA</div>

        <div class="field">
            <span class="field-label">Chave de Acesso da NF-e:</span>
        </div>
        <div class="chave-acesso">
            {{ preg_replace('/(.{4})/', '$1 ', $notaFiscal->nfechave) }}
        </div>

        <table class="dados">
            <tr>
                <td class="label">Numero da NF-e:</td>
                <td>{{ $notaFiscal->numero }}</td>
            </tr>
            <tr>
                <td class="label">Serie:</td>
                <td>{{ $notaFiscal->serie }}</td>
            </tr>
            <tr>
                <td class="label">Data de Emissao:</td>
                <td>{{ $notaFiscal->emissao->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td class="label">Modelo:</td>
                <td>{{ $notaFiscal->modelo }}</td>
            </tr>
        </table>
    </div>

    <!-- DADOS DA CARTA DE CORRECAO -->
    <div class="section">
        <div class="section-title">DADOS DA CARTA DE CORRECAO</div>
        <table class="dados">
            <tr>
                <td class="label">Numero da CC-e:</td>
                <td>{{ $cartaCorrecao->sequencia }}</td>
            </tr>
            <tr>
                <td class="label">Data/Hora do Registro:</td>
                <td>
                    @if($cartaCorrecao->protocolodata)
                        {{ $cartaCorrecao->protocolodata->format('d/m/Y H:i:s') }}
                    @else
                        {{ $cartaCorrecao->criacao->format('d/m/Y H:i:s') }}
                    @endif
                </td>
            </tr>
            @if(!empty($cartaCorrecao->protocolo))
            <tr>
                <td class="label">Protocolo de Autorizacao:</td>
                <td>{{ $cartaCorrecao->protocolo }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Orgao Autorizador:</td>
                <td>SEFAZ/{{ $filial->Pessoa->Cidade->Estado->sigla }}</td>
            </tr>
        </table>
    </div>

    <!-- TEXTO DA CORRECAO -->
    <div class="section">
        <div class="section-title">TEXTO DA CORRECAO</div>
        <p style="margin: 0 0 10px 0; font-style: italic;">
            Correcao efetuada nos termos do Ajuste SINIEF 01/2007:
        </p>
        <div class="texto-correcao">{{ $cartaCorrecao->texto }}</div>
    </div>

    <!-- OBSERVACOES IMPORTANTES -->
    <div class="section">
        <div class="section-title">OBSERVACOES IMPORTANTES</div>
        <div class="observacoes">
            <p style="margin: 0 0 10px 0;"><strong>Esta Carta de Correcao NAO ALTERA:</strong></p>
            <ul>
                <li>Valores do imposto</li>
                <li>Base de calculo</li>
                <li>Aliquota</li>
                <li>Quantidade</li>
                <li>Valor da operacao</li>
                <li>Dados cadastrais que alterem o fato gerador</li>
            </ul>
            <p style="margin: 10px 0 0 0;">
                A CC-e deve ser utilizada exclusivamente para correcoes permitidas pela legislacao vigente.
            </p>
        </div>
    </div>

    <!-- CONTROLE INTERNO -->
    <div class="section">
        <div class="section-title">CONTROLE INTERNO</div>
        <table class="dados">
            <tr>
                <td class="label">Emitido em:</td>
                <td>{{ now()->format('d/m/Y H:i') }}</td>
            </tr>
            @if($cartaCorrecao->UsuarioCriacao)
            <tr>
                <td class="label">Usuario Responsavel:</td>
                <td>{{ $cartaCorrecao->UsuarioCriacao->usuario }}</td>
            </tr>
            @endif
            <tr>
                <td class="label">Sistema:</td>
                <td>MGsis ERP</td>
            </tr>
        </table>
    </div>

    <!-- RODAPE -->
    <div class="footer">
        <p>
            Documento Auxiliar da Carta de Correcao Eletronica (CC-e)<br>
            Referente a NF-e modelo {{ $notaFiscal->modelo }}<br>
            Validade juridica condicionada ao XML autorizado pela SEFAZ
        </p>
    </div>

</body>

</html>
