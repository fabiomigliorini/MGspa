<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 8mm; }

        body { font-family: Arial, Helvetica, sans-serif; font-size: 8pt; color: #000; margin: 0; }

        .recibo {
            border: 2px solid #000;
            padding: 10px 12px;
            margin-bottom: 6mm;
            page-break-inside: avoid;
        }

        .recibo-header { border-bottom: 1.5px solid #000; padding-bottom: 5px; margin-bottom: 6px; }
        .recibo-header h1 { font-size: 12pt; margin: 0; letter-spacing: 1px; }
        .recibo-header .right { float: right; text-align: right; font-size: 7.5pt; }
        .bold { font-weight: bold; }

        .dados { margin-bottom: 6px; font-size: 8pt; }
        .dados p { margin: 1px 0; }

        table.itens { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.itens th, table.itens td { border: 0.3mm solid #666; padding: 3px 6px; }
        table.itens th { background: #e0e0e0; text-align: left; }
        table.itens td.r, table.itens th.r { text-align: right; }
        table.itens tfoot td { background: #d8d8d8; font-weight: bold; }
        .vales { color: #555; font-size: 7pt; }

        .assinatura { margin-top: 26px; text-align: center; }
        .assinatura .linha { display: inline-block; width: 70%; border-top: 1px solid #000; padding-top: 3px; font-size: 7.5pt; }
    </style>
</head>

<body>
    @foreach ($grupos as $eventos)
        @php
            $ev0     = $eventos->first();
            $pc      = $ev0->PeriodoColaborador;
            $col     = $pc?->Colaborador;
            $pessoa  = $col?->Pessoa;
            $filial  = $col?->Filial;
            $filialP = $filial?->Pessoa;
            // Total COM SINAL: saldo já é rubricas + creditos − debitos (com sinal).
            $total   = $eventos->sum(fn ($e) => (float) $e->saldo);
        @endphp
        <div class="recibo">
            <div class="recibo-header">
                <div class="right">
                    <div class="bold">{{ $filial?->filial }}</div>
                    @if ($filialP?->cnpj)
                        <div>CNPJ: {{ formataCnpj($filialP->cnpj) }}</div>
                    @endif
                </div>
                <h1>RECIBO DE ACERTO</h1>
            </div>

            <div class="dados">
                <p><span class="bold">Colaborador:</span> {{ $pessoa?->pessoa }}
                    @if ($pessoa?->fisica && $pessoa?->cnpj)
                        &mdash; CPF: {{ formataCpf($pessoa->cnpj) }}
                    @endif
                </p>
                <p><span class="bold">Período:</span> {{ $periodoLabel }}</p>
            </div>

            <table class="itens">
                <thead>
                    <tr>
                        <th style="width: 18%">Data</th>
                        <th>Forma</th>
                        <th class="r" style="width: 22%">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($eventos as $ev)
                        <tr>
                            <td>{{ $ev->data ? $ev->data->format('d/m/Y') : '' }}</td>
                            <td>
                                {{ $ev->forma_descricao }}
                                @if ($ev->MovimentoTituloS->isNotEmpty())
                                    <div class="vales">
                                        @foreach ($ev->MovimentoTituloS as $m)
                                            {{ optional($m->Titulo)->numero }}
                                            (R$ {{ formataNumero(($m->credito ?? 0) + ($m->debito ?? 0)) }})@if (!$loop->last); @endif
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="r">
                                {{ $ev->saldo < 0 ? '- ' : '' }}R$ {{ formataNumero(abs($ev->saldo)) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2">Total</td>
                        <td class="r">
                            {{ $total < 0 ? '- ' : '' }}R$ {{ formataNumero(abs($total)) }}
                        </td>
                    </tr>
                </tfoot>
            </table>

            <div class="assinatura">
                <div class="linha">{{ $pessoa?->pessoa }}</div>
            </div>
        </div>
    @endforeach
</body>

</html>
