@php
    use Carbon\Carbon;

    $fmtVal = fn($v) => number_format(abs((float) $v), 2, ',', '.');
    $fmtData = fn($d) => $d ? Carbon::parse($d)->format('d/m/Y') : '';
    $fmtCod = fn($c) => '#' . str_pad((string) $c, 8, '0', STR_PAD_LEFT);

    $itens = $repasse->ChequeRepasseChequeS;
    $total = 0.0;
    foreach ($itens as $i) {
        $total += (float) ($i->Cheque->valor ?? 0);
    }
@endphp
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <style>
        body {
            font-family: helvetica;
            font-size: 8pt;
        }

        .report-title {
            font-size: 14pt;
            font-weight: bold;
            border-bottom: 2px solid #000;
            padding-bottom: 3px;
            margin-bottom: 2mm;
        }

        .cabecalho td {
            font-size: 9pt;
            padding: 1px 6px 1px 0;
        }

        .cabecalho .lbl {
            color: #666;
            font-size: 7pt;
            text-transform: uppercase;
        }

        table.itens {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 3mm;
        }

        table.itens th {
            background: #eee;
            border: 1px solid #999;
            padding: 3px 4px;
            text-align: left;
            font-size: 7pt;
            text-transform: uppercase;
        }

        table.itens td {
            border: 1px solid #ccc;
            padding: 3px 4px;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .muted {
            color: #666;
            font-size: 7pt;
        }

        .total-row td {
            border: none;
            padding-top: 4px;
            font-size: 10pt;
            font-weight: bold;
        }

        .footer {
            margin-top: 6mm;
            text-align: right;
            font-size: 6pt;
            color: #666;
            border-top: 1px solid #000;
            padding-top: 2px;
        }
    </style>
</head>
<body>
    <div class="report-title">Borderô de Repasse de Cheques {{ $fmtCod($repasse->codchequerepasse) }}</div>

    <table class="cabecalho">
        <tr>
            <td>
                <div class="lbl">Portador</div>
                <div>{{ $repasse->Portador->portador ?? '' }}</div>
            </td>
            <td>
                <div class="lbl">Data</div>
                <div>{{ $fmtData($repasse->data) }}</div>
            </td>
            <td>
                <div class="lbl">Situação</div>
                <div>{{ $repasse->inativo ? 'Inativo' : 'Ativo' }}</div>
            </td>
            <td>
                <div class="lbl">Qtde Cheques</div>
                <div>{{ count($itens) }}</div>
            </td>
        </tr>
        @if (!empty($repasse->observacoes))
            <tr>
                <td colspan="4">
                    <div class="lbl">Observações</div>
                    <div>{{ $repasse->observacoes }}</div>
                </td>
            </tr>
        @endif
    </table>

    <table class="itens">
        <thead>
            <tr>
                <th style="width: 14mm">#</th>
                <th style="width: 30mm">Banco / Ag.</th>
                <th style="width: 28mm">Conta / Nº</th>
                <th>Cliente / Emitentes</th>
                <th style="width: 22mm" class="text-center">Vencimento</th>
                <th style="width: 24mm" class="text-right">Valor</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($itens as $i)
                @php $c = $i->Cheque; @endphp
                <tr>
                    <td>{{ $fmtCod($c->codcheque) }}</td>
                    <td>
                        {{ $c->Banco->banco ?? $c->codbanco }}<br>
                        <span class="muted">Ag. {{ $c->agencia }}</span>
                    </td>
                    <td>
                        {{ $c->contacorrente }}<br>
                        <span class="muted">Nº {{ $c->numero }}</span>
                    </td>
                    <td>
                        {{ $c->Pessoa->pessoa ?? '' }}
                        @foreach ($c->ChequeEmitenteS as $emit)
                            <br><span class="muted">{{ $emit->emitente }}</span>
                        @endforeach
                    </td>
                    <td class="text-center">{{ $fmtData($c->vencimento) }}</td>
                    <td class="text-right">{{ $fmtVal($c->valor) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="5" class="text-right">TOTAL</td>
                <td class="text-right">{{ $fmtVal($total) }}</td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Emitido em {{ Carbon::now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
