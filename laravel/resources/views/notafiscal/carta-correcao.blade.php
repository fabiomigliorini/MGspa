<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 1cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.2;
            color: #000;
        }

        .header-box {
            border: 2px solid #000;
            padding: 8px;
            text-align: center;
            margin-bottom: 8px;
        }

        .header-title {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 2px;
        }

        .header-subtitle {
            font-size: 9pt;
            color: #333;
        }

        .section {
            margin-bottom: 8px;
            border: 1px solid #ccc;
            padding: 6px;
        }

        .section-title {
            font-size: 9pt;
            font-weight: bold;
            background-color: #f0f0f0;
            padding: 3px 6px;
            margin: -6px -6px 6px -6px;
            border-bottom: 1px solid #ccc;
        }

        .chave-acesso {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            letter-spacing: 1px;
            background-color: #f9f9f9;
            padding: 4px;
            border: 1px dashed #999;
            text-align: center;
            margin: 4px 0;
        }

        .texto-correcao {
            background-color: #fffef0;
            border: 1px solid #e6e6b8;
            padding: 8px;
            margin: 4px 0;
            white-space: pre-wrap;
            font-size: 9pt;
        }

        .observacoes {
            background-color: #fff5f5;
            border: 1px solid #ffcccc;
            padding: 6px;
            font-size: 8pt;
        }

        .observacoes ul {
            margin: 2px 0;
            padding-left: 15px;
        }

        .observacoes li {
            margin-bottom: 1px;
        }

        .footer {
            margin-top: 10px;
            padding-top: 6px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            text-align: center;
            color: #666;
        }

        .footer p {
            margin: 0;
        }

        .sem-valor-fiscal {
            font-size: 10pt;
            font-weight: bold;
            color: #cc0000;
            text-align: center;
            padding: 4px;
            border: 2px solid #cc0000;
            margin: 8px 0;
        }

        table.dados {
            width: 100%;
            border-collapse: collapse;
        }

        table.dados td {
            padding: 1px 0;
            vertical-align: top;
        }

        table.dados td.label {
            width: 140px;
            font-weight: bold;
        }

        .two-columns {
            display: table;
            width: 100%;
        }

        .two-columns > div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }

        .two-columns > div:last-child {
            padding-right: 0;
            padding-left: 10px;
        }
    </style>
</head>

<body>

    <!-- CABECALHO -->
    <div class="header-box">
        <div class="header-title">CARTA DE CORRECAO ELETRONICA</div>
        <div class="header-subtitle">(DOCUMENTO AUXILIAR - SEM VALOR FISCAL)</div>
    </div>

    <div class="sem-valor-fiscal">
        DOCUMENTO AUXILIAR - SEM VALOR FISCAL
    </div>

    <!-- EMITENTE E NF-e LADO A LADO -->
    <div class="two-columns">
        <div>
            <!-- IDENTIFICACAO DO EMITENTE -->
            <div class="section" style="margin-bottom: 0;">
                <div class="section-title">EMITENTE</div>
                <table class="dados">
                    <tr>
                        <td colspan="2"><strong>{{ $filial->Pessoa->pessoa }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label">CNPJ:</td>
                        <td>{{ formataCnpj($filial->Pessoa->cnpj) }}</td>
                    </tr>
                    @if(!empty($filial->Pessoa->ie))
                    <tr>
                        <td class="label">IE:</td>
                        <td>{{ $filial->Pessoa->ie }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Endereco:</td>
                        <td>{{ $filial->Pessoa->Cidade->cidade }}/{{ $filial->Pessoa->Cidade->Estado->sigla }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <div>
            <!-- DADOS DA CARTA DE CORRECAO -->
            <div class="section" style="margin-bottom: 0;">
                <div class="section-title">DADOS DA CC-e</div>
                <table class="dados">
                    <tr>
                        <td class="label">Numero CC-e:</td>
                        <td>{{ $cartaCorrecao->sequencia }}</td>
                    </tr>
                    <tr>
                        <td class="label">Data/Hora:</td>
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
                        <td class="label">Protocolo:</td>
                        <td>{{ $cartaCorrecao->protocolo }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td class="label">Orgao:</td>
                        <td>SEFAZ/{{ $filial->Pessoa->Cidade->Estado->sigla }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- IDENTIFICACAO DA NF-e CORRIGIDA -->
    <div class="section">
        <div class="section-title">NF-e CORRIGIDA</div>
        <div class="chave-acesso">
            <strong>Chave:</strong> {{ preg_replace('/(.{4})/', '$1 ', $notaFiscal->nfechave) }}
        </div>
        <table class="dados">
            <tr>
                <td><strong>Numero:</strong> {{ $notaFiscal->numero }}</td>
                <td><strong>Serie:</strong> {{ $notaFiscal->serie }}</td>
                <td><strong>Emissao:</strong> {{ $notaFiscal->emissao->format('d/m/Y') }}</td>
                <td><strong>Modelo:</strong> {{ $notaFiscal->modelo }}</td>
            </tr>
        </table>
    </div>

    <!-- TEXTO DA CORRECAO -->
    <div class="section">
        <div class="section-title">TEXTO DA CORRECAO</div>
        <div class="texto-correcao">{{ $cartaCorrecao->texto }}</div>
    </div>

    <!-- OBSERVACOES IMPORTANTES -->
    <div class="section">
        <div class="section-title">OBSERVACOES IMPORTANTES</div>
        <div class="observacoes">
            <strong>Esta CC-e NAO ALTERA:</strong> valores do imposto, base de calculo, aliquota, quantidade, valor da operacao, dados cadastrais que alterem o fato gerador.
            <br>A CC-e deve ser utilizada exclusivamente para correcoes permitidas pela legislacao vigente (Ajuste SINIEF 01/2007).
        </div>
    </div>

    <!-- RODAPE -->
    <div class="footer">
        <p>
            Documento Auxiliar da Carta de Correcao Eletronica (CC-e) | NF-e modelo {{ $notaFiscal->modelo }} | Validade juridica condicionada ao XML autorizado pela SEFAZ
            <br>Emitido em {{ now()->format('d/m/Y H:i') }} | MGsis ERP
        </p>
    </div>

</body>

</html>
