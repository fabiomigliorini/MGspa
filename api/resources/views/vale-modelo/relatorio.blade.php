@php
    $num = fn($v, $dec = 2) => number_format((float) $v, $dec, ',', '.');

    // Quantidade quase sempre e inteira (2 cadernos, 1 tesoura); so mostra
    // casas quando a fracao existe de verdade.
    $qtd = fn($v) => floor((float) $v) == (float) $v ? $num($v, 0) : $num($v, 3);

    // A4 retrato: 210 - 12 - 12 = 186mm uteis. O layout fica em 184mm; a folga
    // e porque o mPDF arredonda pra cima na borda de cada celula.
    $cols = [28, 88, 18, 25, 25]; // = 184mm

    $temAvulso = (float) $modelo->valoravulso > 0;
@endphp
<style>
    body {
        font-family: helvetica;
        font-size: 8pt;
    }

    .doc-title {
        font-size: 13pt;
        font-weight: bold;
        text-align: right;
    }

    .doc-sub {
        font-size: 7pt;
        color: #555;
        text-align: right;
    }

    .regua {
        border-bottom: 2px solid #000;
        height: 1px;
    }

    .footer {
        text-align: right;
        font-size: 6pt;
        color: #666;
        border-top: 1px solid #000;
        padding-top: 2px;
    }

    table.identificacao {
        width: 184mm;
        border-collapse: collapse;
        margin-bottom: 4mm;
    }

    table.identificacao td {
        padding: 0 6px 1.5mm 0;
        vertical-align: top;
    }

    .lbl {
        color: #666;
        font-size: 6.5pt;
        text-transform: uppercase;
    }

    .val {
        font-size: 10pt;
    }

    .obs {
        font-size: 7.5pt;
        color: #444;
    }

    table.itens {
        width: 184mm;
        border-collapse: collapse;
        table-layout: fixed;
    }

    table.itens th {
        background: #eee;
        border-bottom: 1px solid #999;
        padding: 1.2mm 1mm;
        text-align: left;
        font-weight: bold;
        font-size: 7.5pt;
    }

    table.itens td {
        padding: 1mm;
        vertical-align: top;
        overflow: hidden;
        border-top: 1px solid #ddd;
    }

    .r {
        text-align: right;
    }

    td.cod {
        color: #555;
        white-space: nowrap;
    }

    td.tot {
        font-weight: bold;
    }

    tr.somatoria td {
        border-top: 1px solid #999;
        background: #f6f6f6;
        font-weight: bold;
    }

    tr.face td {
        border-top: 2px solid #333;
        background: #ddd;
        font-weight: bold;
        font-size: 11pt;
        padding: 1.6mm 1mm;
    }

    .vazio {
        padding: 6mm 0;
        color: #777;
        font-style: italic;
    }
</style>

<htmlpageheader name="page-header">
    <table style="width: 184mm">
        <tr>
            @if ($logo)
                {{-- Largura e altura explicitas e na proporcao do arquivo
                     (402x76): so com a largura o mPDF estica a imagem. --}}
                <td style="vertical-align: middle">
                    <img src="{{ $logo }}" style="width: 45mm; height: 8.5mm">
                </td>
            @endif
            <td style="width: 62mm; vertical-align: middle">
                <div class="doc-title">Modelo de Vale Compras</div>
                <div class="doc-sub">Relação de itens e valores</div>
            </td>
        </tr>
    </table>
    <div class="regua"></div>
</htmlpageheader>

<htmlpagefooter name="page-footer">
    <div class="footer">
        MGspa &mdash; Impresso em {DATE j/m/Y H:i} &mdash; Página {PAGENO} de {nbpg}
    </div>
</htmlpagefooter>

<sethtmlpageheader name="page-header" value="on" show-this-page="1">
<sethtmlpagefooter name="page-footer" value="on" show-this-page="1">

<table class="identificacao">
    <tr>
        <td style="width: 92mm">
            <div class="lbl">Favorecido</div>
            <div class="val">
                @if ($modelo->codpessoafavorecido)
                    {{ $modelo->PessoaFavorecido->fantasia ?: $modelo->PessoaFavorecido->pessoa }}
                @else
                    Ao portador
                @endif
            </div>
        </td>
        <td>
            <div class="lbl">Modelo</div>
            <div class="val">{{ $modelo->modelo }}</div>
        </td>
    </tr>
    @if (!empty($modelo->observacoes))
        <tr>
            <td colspan="2">
                <div class="lbl">Observações</div>
                <div class="obs">{{ $modelo->observacoes }}</div>
            </td>
        </tr>
    @endif
</table>

@if ($itens->isEmpty() && !$temAvulso)
    <div class="vazio">Este modelo não tem itens nem valor avulso.</div>
@else
    <table class="itens">
        <colgroup>
            @foreach ($cols as $w)
                <col style="width:{{ $w }}mm">
            @endforeach
        </colgroup>
        {{-- Modelo avulso puro nao tem lista: a tabela fica so com a face. --}}
        @if ($itens->isNotEmpty())
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descrição</th>
                    <th class="r">Qtde</th>
                    <th class="r">Preço Unit.</th>
                    <th class="r">Total</th>
                </tr>
            </thead>
        @endif
        <tbody>
            @foreach ($itens as $item)
                <tr>
                    <td class="cod">{{ $item['barras'] }}</td>
                    <td>{{ $item['produto'] }}</td>
                    <td class="r">{{ $qtd($item['quantidade']) }}</td>
                    <td class="r">{{ $num($item['valorunitario']) }}</td>
                    <td class="r tot">{{ $num($item['valorprodutos']) }}</td>
                </tr>
            @endforeach

            {{-- As duas parcelas so aparecem quando existe avulso; num kit puro
                 o subtotal dos produtos repetiria a face, logo abaixo. --}}
            @if ($temAvulso)
                @if ($itens->isNotEmpty())
                    <tr class="somatoria">
                        <td colspan="4" class="r">
                            Produtos &mdash; {{ count($itens) }}
                            {{ count($itens) == 1 ? 'item' : 'itens' }}
                        </td>
                        <td class="r">{{ $num($modelo->valorprodutos) }}</td>
                    </tr>
                @endif
                <tr class="somatoria">
                    <td colspan="4" class="r">Avulso</td>
                    <td class="r">{{ $num($modelo->valoravulso) }}</td>
                </tr>
            @endif

            <tr class="face">
                <td colspan="4" class="r">TOTAL</td>
                <td class="r">{{ $num($modelo->valorvale) }}</td>
            </tr>
        </tbody>
    </table>
@endif
