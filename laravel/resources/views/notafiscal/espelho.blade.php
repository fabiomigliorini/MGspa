<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 0.8cm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
            line-height: 1.2;
            color: #000;
            margin: 0;
            padding: 0;
        }

        .header-title {
            background-color: #1976D2;
            color: #fff;
            font-size: 10pt;
            font-weight: bold;
            padding: 4px 8px;
            margin-bottom: 0;
            border-radius: 4px;
        }

        .section {
            margin-bottom: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            overflow: hidden;
        }

        .section-title {
            background-color: #1976D2;
            color: #fff;
            font-size: 8pt;
            font-weight: bold;
            padding: 3px 6px;
        }

        .section-content {
            padding: 6px;
        }

        table.dados {
            width: 100%;
            border-collapse: collapse;
        }

        table.dados td {
            padding: 2px 4px;
            vertical-align: top;
        }

        table.dados td.label {
            font-size: 7pt;
            color: #666;
        }

        table.dados td.value {
            font-weight: bold;
        }

        .three-columns {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .three-columns>div {
            display: table-cell;
            vertical-align: top;
            padding-right: 4px;
        }

        .three-columns>div:last-child {
            padding-right: 0;
        }

        .two-columns {
            display: table;
            width: 100%;
            table-layout: fixed;
        }

        .two-columns>div {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 4px;
        }

        .two-columns>div:last-child {
            padding-right: 0;
            padding-left: 4px;
        }

        .chave-acesso {
            font-family: 'Courier New', monospace;
            font-size: 9pt;
            letter-spacing: 1px;
        }

        .status-autorizada {
            background-color: #4CAF50;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            display: inline-block;
        }

        .status-digitacao {
            background-color: #FFC107;
            color: #000;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            display: inline-block;
        }

        .status-cancelada {
            background-color: #F44336;
            color: #fff;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 8pt;
            display: inline-block;
        }

        table.itens {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            font-size: 7pt;
            border-radius: 4px;
            overflow: hidden;
        }

        table.itens th {
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-left: none;
            padding: 3px 4px;
            text-align: left;
            font-weight: bold;
        }

        table.itens th:first-child {
            border-left: 1px solid #ddd;
            border-top-left-radius: 4px;
        }

        table.itens th:last-child {
            border-top-right-radius: 4px;
        }

        table.itens td {
            border: 1px solid #ddd;
            border-top: none;
            border-left: none;
            padding: 3px 4px;
        }

        table.itens td:first-child {
            border-left: 1px solid #ddd;
        }

        table.itens tr:last-child td:first-child {
            border-bottom-left-radius: 4px;
        }

        table.itens tr:last-child td:last-child {
            border-bottom-right-radius: 4px;
        }

        table.itens td.number {
            text-align: right;
        }

        table.itens td.center {
            text-align: center;
        }

        .totais-grid {
            display: table;
            width: 100%;
        }

        .totais-grid>div {
            display: table-cell;
            text-align: center;
            padding: 4px;
            border-right: 1px solid #ddd;
        }

        .totais-grid>div:last-child {
            border-right: none;
        }

        .totais-grid .label {
            font-size: 7pt;
            color: #666;
        }

        .totais-grid .value {
            font-size: 9pt;
            font-weight: bold;
        }

        .valor-total {
            color: #1976D2;
            font-size: 12pt;
            font-weight: bold;
        }

        .footer {
            margin-top: 8px;
            padding-top: 4px;
            border-top: 1px solid #ccc;
            font-size: 7pt;
            text-align: center;
            color: #666;
        }

        .observacoes-text {
            background-color: #f9f9f9;
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 8pt;
            white-space: pre-wrap;
        }

        .page-break {
            page-break-before: always;
        }
    </style>
</head>

<body>

    <!-- CABECALHO PRINCIPAL -->
    <div class="header-title">
        #{{ str_pad($notaFiscal->codnotafiscal, 8, '0', STR_PAD_LEFT) }} -
        NF-e ({{ $notaFiscal->modelo }}) {{ str_pad($notaFiscal->numero ?? '---', 8, '0', STR_PAD_LEFT) }} - Serie
        {{ $notaFiscal->serie ?? '---' }}
    </div>

    <!-- TRES COLUNAS: FILIAL, TRANSPORTE, NFE -->
    <div class="three-columns" style="margin-top: 6px;">
        <!-- FILIAL -->
        <div style="width: 25%;">
            <div class="section">
                <div class="section-title">Filial</div>
                <div class="section-content">
                    <table class="dados">
                        <tr>
                            <td class="label">Filial / Local de Estoque</td>
                        </tr>
                        <tr>
                            <td class="value">{{ $filial->filial ?? '---' }} /
                                {{ $notaFiscal->EstoqueLocal->estoquelocal ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Natureza de Operacao</td>
                        </tr>
                        <tr>
                            <td class="value">{{ $notaFiscal->Operacao->operacao ?? '---' }} |
                                {{ $notaFiscal->NaturezaOperacao->naturezaoperacao ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Emissao</td>
                        </tr>
                        <tr>
                            <td class="value">
                                {{ $notaFiscal->emissao ? $notaFiscal->emissao->format('d/m/Y, H:i:s') : '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Saida/Entrada</td>
                        </tr>
                        <tr>
                            <td class="value">
                                {{ $notaFiscal->saida ? $notaFiscal->saida->format('d/m/Y, H:i:s') : '---' }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- TRANSPORTE -->
        <div style="width: 20%;">
            <div class="section">
                <div class="section-title">Transporte</div>
                <div class="section-content">
                    <table class="dados">
                        <tr>
                            <td class="label">Frete</td>
                        </tr>
                        <tr>
                            <td class="value">
                                {{ \Mg\NotaFiscal\NotaFiscalService::FRETE_LABELS[$notaFiscal->frete] ?? 'Sem frete' }}
                            </td>
                        </tr>
                        @if ($notaFiscal->PessoaTransportador)
                            <tr>
                                <td class="label">Transportador</td>
                            </tr>
                            <tr>
                                <td class="value">{{ $notaFiscal->PessoaTransportador->pessoa }}</td>
                            </tr>
                        @endif
                        @if ($notaFiscal->placa)
                            <tr>
                                <td class="label">Placa</td>
                            </tr>
                            <tr>
                                <td class="value">{{ $notaFiscal->placa }}
                                    {{ $notaFiscal->EstadoPlaca ? '/ ' . $notaFiscal->EstadoPlaca->sigla : '' }}</td>
                            </tr>
                        @endif
                        @if ($notaFiscal->volumes || $notaFiscal->volumesespecie || $notaFiscal->volumesmarca || $notaFiscal->volumesnumero)
                            <tr>
                                <td class="label">Volumes</td>
                            </tr>
                            <tr>
                                <td class="value">
                                    {{ $notaFiscal->volumes }}
                                    {{ $notaFiscal->volumesespecie }}
                                    {{ $notaFiscal->volumesmarca }}
                                    {{ $notaFiscal->volumesnumero }}
                        @endif
                        </td>
                        </tr>
                        @if ($notaFiscal->pesobruto)
                            <tr>
                                <td class="label">Peso Bruto</td>
                            </tr>
                            <tr>
                                <td class="value">
                                    {{ number_format($notaFiscal->pesobruto, 3, ',', '.') }} kg
                                </td>
                            </tr>
                        @endif
                        @if ($notaFiscal->pesoliquido)
                            <tr>
                                <td class="label">Peso Líquido</td>
                            </tr>
                            <tr>
                                <td class="value">
                                    {{ number_format($notaFiscal->pesoliquido, 3, ',', '.') }} kg
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>

        <!-- NFE -->
        <div style="width: 55%;">
            <div class="section">
                <div class="section-title">&lt;&gt; NFe</div>
                <div class="section-content">
                    <table class="dados">
                        <tr>
                            <td class="label">NF-e ({{ $notaFiscal->modelo }})</td>
                        </tr>
                        <tr>
                            <td class="value">{{ str_pad($notaFiscal->numero ?? '---', 8, '0', STR_PAD_LEFT) }} -
                                Serie {{ $notaFiscal->serie ?? '---' }}</td>
                        </tr>
                        <tr>
                            <td class="label">Chave</td>
                        </tr>
                        <tr>
                            <td class="value chave-acesso">
                                {{ $notaFiscal->nfechave ? preg_replace('/(.{4})/', '$1 ', $notaFiscal->nfechave) : '---' }}
                            </td>
                        </tr>
                        <tr>
                            <td class="label">Status da Nota</td>
                        </tr>
                        <tr>
                            <td>
                                @if ($notaFiscal->status == \Mg\NotaFiscal\NotaFiscalService::STATUS_AUTORIZADA)
                                    <span
                                        class="status-autorizada">{{ \Mg\NotaFiscal\NotaFiscalService::STATUS_LABELS[$notaFiscal->status] }}</span>
                                @elseif(
                                    $notaFiscal->status == \Mg\NotaFiscal\NotaFiscalService::STATUS_CANCELADA ||
                                        $notaFiscal->status == \Mg\NotaFiscal\NotaFiscalService::STATUS_DENEGADA)
                                    <span
                                        class="status-cancelada">{{ \Mg\NotaFiscal\NotaFiscalService::STATUS_LABELS[$notaFiscal->status] }}</span>
                                @else
                                    <span
                                        class="status-digitacao">{{ \Mg\NotaFiscal\NotaFiscalService::STATUS_LABELS[$notaFiscal->status] ?? $notaFiscal->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @if ($notaFiscal->nfeautorizacao)
                            <tr>
                                <td class="label">Autorizacao</td>
                            </tr>
                            <tr>
                                <td class="value">
                                    {{ $notaFiscal->nfeautorizacao }}{{ $notaFiscal->nfedataautorizacao ? ' ' . $notaFiscal->nfedataautorizacao->format('d/m/Y, H:i:s') : '' }}
                                </td>
                            </tr>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- REMETENTE/DESTINATARIO -->
    <div class="section">
        <div class="section-title">{{ $notaFiscal->emitida ? 'Destinatario' : 'Remetente' }}</div>
        <div class="section-content">
            <table class="dados" style="width: 100%;">
                <tr>
                    <td style="width: 50%;">
                        <span class="label">Nome / Razao Social</span><br>
                        <strong>{{ $notaFiscal->Pessoa->pessoa ?? '---' }}</strong>
                    </td>
                    <td style="width: 25%;">
                        <span class="label">CNPJ/IE</span><br>
                        <strong>
                            {{ formataCnpjCpf($notaFiscal->Pessoa->cnpj, $notaFiscal->Pessoa->fisica) }}
                            <template v-if="$notaFiscal->Pessoa->ie">
                                | {{ $notaFiscal->Pessoa->ie }}
                            </template>
                        </strong>
                    </td>
                    <td style="width: 25%;">
                        <span class="label">E-MAIL</span><br>
                        {{ $notaFiscal->Pessoa->email ?? '---' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">Endereco Fiscal</span><br>
                        {{ $notaFiscal->Pessoa->endereco ?? '---' }}{{ $notaFiscal->Pessoa->numero ? ', ' . $notaFiscal->Pessoa->numero : '' }}
                    </td>
                    <td>
                        <span class="label">Bairro</span><br>
                        {{ $notaFiscal->Pessoa->bairro ?? '---' }}
                    </td>
                    <td>
                        <span class="label">Cidade</span><br>
                        {{ $notaFiscal->Pessoa->Cidade->cidade ?? '---' }} /
                        {{ $notaFiscal->Pessoa->Cidade->Estado->sigla ?? '---' }}
                    </td>
                </tr>
                <tr>
                    <td>
                        <span class="label">CEP</span><br>
                        {{ $notaFiscal->Pessoa->cep ?? '---' }}
                    </td>
                    <td colspan="2">
                        <span class="label">Telefone</span><br>
                        {{ $notaFiscal->Pessoa->telefone1 ?? '---' }}
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- TOTAIS -->
    <div class="section">
        <div class="section-title">Totais</div>
        <div class="section-content">
            <div class="totais-grid">
                <div>
                    <div class="label">ICMS</div>
                    <div class="value">{{ number_format($totais['icmsvalor'], 2, ',', '.') }} /
                        {{ number_format($totais['icmsbase'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">ICMS ST</div>
                    <div class="value">{{ number_format($totais['icmsstvalor'], 2, ',', '.') }} /
                        {{ number_format($totais['icmsstbase'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">IPI</div>
                    <div class="value">{{ number_format($totais['ipivalor'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">PIS</div>
                    <div class="value">{{ number_format($totais['pisvalor'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">COFINS</div>
                    <div class="value">{{ number_format($totais['cofinsvalor'], 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">IBPT</div>
                    <div class="value">{{ number_format($totais['ibpt'], 2, ',', '.') }}</div>
                </div>
            </div>
            <div class="totais-grid" style="margin-top: 4px; border-top: 1px solid #ddd; padding-top: 4px;">
                <div>
                    <div class="label">Produtos</div>
                    <div class="value">{{ number_format($notaFiscal->valorprodutos ?? 0, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Desconto</div>
                    <div class="value">{{ number_format($notaFiscal->valordesconto ?? 0, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Frete</div>
                    <div class="value">{{ number_format($notaFiscal->valorfrete ?? 0, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Seguro</div>
                    <div class="value">{{ number_format($notaFiscal->valorseguro ?? 0, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Outras Despesas</div>
                    <div class="value">{{ number_format($notaFiscal->valoroutras ?? 0, 2, ',', '.') }}</div>
                </div>
                <div>
                    <div class="label">Total</div>
                    <div class="valor-total">{{ number_format($notaFiscal->valortotal ?? 0, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- ITENS -->
    <div class="section">
        <div class="section-title">Itens ({{ $notaFiscal->NotaFiscalProdutoBarraS->count() }})</div>
        <div class="section-content" style="padding: 0;">
            <table class="itens">
                <thead>
                    <tr>
                        <th style="width: 3%;">#</th>
                        <th style="width: 30%;">Descricao</th>
                        <th style="width: 10%;">Barras</th>
                        <th style="width: 6%;">UN</th>
                        <th style="width: 8%;">Quant</th>
                        <th style="width: 10%;">Preco</th>
                        <th style="width: 10%;">Total</th>
                        <th style="width: 12%;">NCM/CEST</th>
                        <th style="width: 5%;">CST</th>
                        <th style="width: 6%;">CFOP</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($notaFiscal->NotaFiscalProdutoBarraS as $index => $item)
                        <tr>
                            <td class="center">{{ $item->ordem }}</td>
                            <td>
                                #{{ str_pad($item->ProdutoBarra->codproduto, 6, '0', STR_PAD_LEFT) }} -
                                {{ $item->descricaoalternativa ?: $item->ProdutoBarra->descricao ?? '---' }}
                            </td>
                            <td>{{ $item->ProdutoBarra->barras ?? '---' }}</td>
                            <td class="center">{{ $item->ProdutoBarra->unidade ?? 'UN' }}</td>
                            <td class="number">{{ number_format($item->quantidade, 4, ',', '.') }}</td>
                            <td class="number">{{ number_format($item->valorunitario, 2, ',', '.') }}</td>
                            <td class="number"><strong>{{ number_format($item->valortotal, 2, ',', '.') }}</strong>
                            </td>
                            <td class="center">
                                {{ $item->ProdutoBarra->ProdutoVariacao->Produto->Ncm->ncm ?? '---' }}
                                @if ($item->ProdutoBarra->ProdutoVariacao->Produto->Cest)
                                    <br><small>{{ $item->ProdutoBarra->ProdutoVariacao->Produto->Cest->cest }}</small>
                                @endif
                            </td>
                            <td class="center">{{ $item->csosn ?? ($item->icmscst ?? '---') }}</td>
                            <td class="center">{{ $item->codcfop ?? '---' }}</td>
                        </tr>
                        @if ($item->icmsbase > 0 || $item->ipivalor > 0)
                            <tr style="background-color: #f9f9f9;">
                                <td></td>
                                <td colspan="9" style="font-size: 7pt; color: #666;">
                                    @if ($item->icmsbase > 0)
                                        <strong>ICMS:</strong> Base {{ number_format($item->icmsbase, 2, ',', '.') }} |
                                        Aliq {{ number_format($item->icmspercentual, 2, ',', '.') }}% | Valor
                                        {{ number_format($item->icmsvalor, 2, ',', '.') }}
                                    @endif
                                    @if ($item->ipivalor > 0)
                                        &nbsp;&nbsp;<strong>IPI:</strong>
                                        {{ number_format($item->ipivalor, 2, ',', '.') }}
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- OBSERVACOES -->
    @if ($notaFiscal->observacoes)
        <div class="section">
            <div class="section-title">Observacoes</div>
            <div class="section-content">
                <div class="observacoes-text">{{ $notaFiscal->observacoes }}</div>
            </div>
        </div>
    @endif

    <!-- FORMAS DE PAGAMENTO E DUPLICATAS -->
    <div class="two-columns">
        <div>
            <div class="section">
                <div class="section-title">Formas de Pagamento ({{ $notaFiscal->NotaFiscalPagamentoS->count() }})
                </div>
                <div class="section-content">
                    @if ($notaFiscal->NotaFiscalPagamentoS->count() > 0)
                        @foreach ($notaFiscal->NotaFiscalPagamentoS as $pagamento)
                            <div style="margin-bottom: 4px;">
                                <strong>{{ $pagamento->descricao ?? ($pagamento->tipodescricao ?? 'Pagamento') }}</strong>
                                - R$ {{ number_format($pagamento->valorpagamento, 2, ',', '.') }}
                            </div>
                        @endforeach
                    @else
                        <span style="color: #999;">Nenhuma forma de pagamento adicionada</span>
                    @endif
                </div>
            </div>
        </div>
        <div>
            <div class="section">
                <div class="section-title">Duplicatas ({{ $notaFiscal->NotaFiscalDuplicatasS->count() }})</div>
                <div class="section-content">
                    @if ($notaFiscal->NotaFiscalDuplicatasS->count() > 0)
                        @foreach ($notaFiscal->NotaFiscalDuplicatasS as $duplicata)
                            <div style="margin-bottom: 4px;">
                                <strong>{{ $duplicata->fatura }}</strong>
                                - R$ {{ number_format($duplicata->valor, 2, ',', '.') }}
                                - Venc: {{ $duplicata->vencimento ? $duplicata->vencimento->format('d/m/Y') : '---' }}
                            </div>
                        @endforeach
                    @else
                        <span style="color: #999;">Nenhuma duplicata adicionada</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- NOTAS REFERENCIADAS E CARTAS DE CORRECAO -->
    <div class="two-columns">
        <div>
            <div class="section">
                <div class="section-title">Notas Fiscais Referenciadas
                    ({{ $notaFiscal->NotaFiscalReferenciadaS->count() }})</div>
                <div class="section-content">
                    @if ($notaFiscal->NotaFiscalReferenciadaS->count() > 0)
                        @foreach ($notaFiscal->NotaFiscalReferenciadaS as $referenciada)
                            <div style="margin-bottom: 4px; font-family: 'Courier New', monospace; font-size: 7pt;">
                                {{ preg_replace('/(.{4})/', '$1 ', $referenciada->nfechave) }}
                            </div>
                        @endforeach
                    @else
                        <span style="color: #999;">Nenhuma nota fiscal referenciada</span>
                    @endif
                </div>
            </div>
        </div>
        <div>
            <div class="section">
                <div class="section-title">Cartas de Correcao ({{ $notaFiscal->NotaFiscalCartaCorrecaoS->count() }})
                </div>
                <div class="section-content">
                    @if ($notaFiscal->NotaFiscalCartaCorrecaoS->count() > 0)
                        @foreach ($notaFiscal->NotaFiscalCartaCorrecaoS as $carta)
                            <div style="margin-bottom: 4px;">
                                <strong>Seq {{ $carta->sequencia }}</strong>
                                -
                                {{ $carta->protocolodata ? $carta->protocolodata->format('d/m/Y') : ($carta->criacao ? $carta->criacao->format('d/m/Y') : '---') }}
                            </div>
                        @endforeach
                    @else
                        <span style="color: #999;">Nenhuma carta de correcao emitida</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- RODAPE -->
    <div class="footer">
        <p>
            Espelho da Nota Fiscal | Emitido em {{ now()->format('d/m/Y H:i') }} | MGsis ERP
        </p>
    </div>

</body>

</html>
