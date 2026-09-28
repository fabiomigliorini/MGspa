@php
    $num = fn($v) => number_format((float) $v, 2, ',', '.');
    $data = fn($v) => \Carbon\Carbon::parse($v)->format('d/m/Y');

    $filtro = ['Situação: ' . ['ativo' => 'Ativos', 'cancelado' => 'Cancelados', 'todos' => 'Todos'][$filtros['situacao'] ?? 'ativo']];
    if (!empty($filtros['de'])) $filtro[] = 'De ' . $data($filtros['de']);
    if (!empty($filtros['ate'])) $filtro[] = 'Até ' . $data($filtros['ate']);
    if (!empty($filtros['codnegocio'])) $filtro[] = 'Negócio #' . str_pad($filtros['codnegocio'], 8, '0', STR_PAD_LEFT);
    if (isset($filtros['valorde'])) $filtro[] = 'Valor de R$ ' . $num($filtros['valorde']);
    if (isset($filtros['valorate'])) $filtro[] = 'Valor até R$ ' . $num($filtros['valorate']);
    if (!empty($filtros['busca'])) $filtro[] = 'Busca: ' . $filtros['busca'];
    if (!empty($filtros['comsaldo'])) $filtro[] = 'Somente com saldo';

    $total = array_sum(array_map(fn($v) => (float) $v->valorvale, $vales));
    $saldo = array_sum(array_map(fn($v) => $v->codtitulo ? -1 * (float) $v->saldo : 0, $vales));
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: helvetica; font-size: 8pt; color: #222; }
        h1 { font-size: 14pt; margin: 0 0 2mm; }
        .filtros { color: #555; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border-bottom: 0.2mm solid #ccc; padding: 1.5mm 1.2mm; vertical-align: top; }
        th { background: #eee; text-align: left; }
        tfoot td { font-weight: bold; border-bottom: none; }
        .r { text-align: right; }
        .cap { color: #666; font-size: 7pt; }
        .cancelado { color: #999; }
    </style>
</head>
<body>
<h1>Vales Compras Emitidos</h1>
<div class="filtros">{{ implode(' · ', $filtro) }}</div>
<table>
    <thead>
    <tr>
        <th style="width: 10%">Data</th>
        <th class="r" style="width: 11%">Valor</th>
        <th class="r" style="width: 14%">Saldo</th>
        <th style="width: 22%">Aluno</th>
        <th style="width: 33%">Favorecido</th>
        <th style="width: 10%">Situação</th>
    </tr>
    </thead>
    <tbody>
    @foreach($vales as $vale)
        <tr class="{{ $vale->cancelado ? 'cancelado' : '' }}">
            <td>{{ $data($vale->lancamento) }}</td>
            <td class="r">
                {{ $num($vale->valorvale) }}<br>
                <span class="cap">#{{ str_pad($vale->codnegocio, 8, '0', STR_PAD_LEFT) }}</span>
            </td>
            <td class="r">
                @if($vale->codtitulo)
                    {{ $num(-1 * (float) $vale->saldo + 0) }}<br>
                    <span class="cap">{{ $vale->numero }}</span>
                @endif
            </td>
            <td>
                {{ $vale->aluno }}
                @if($vale->turma)<br><span class="cap">{{ $vale->turma }}</span>@endif
            </td>
            <td>
                {{ $vale->codpessoafavorecido == 1 ? 'Ao portador' : $vale->favorecido }}
                @if($vale->modelo)<br><span class="cap">{{ $vale->modelo }}</span>@endif
            </td>
            <td>{{ $vale->cancelado ? 'Cancelado' : 'Ativo' }}</td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <td>{{ count($vales) }} vales</td>
        <td class="r">{{ $num($total) }}</td>
        <td class="r">{{ $num($saldo) }}</td>
        <td colspan="3"></td>
    </tr>
    </tfoot>
</table>
</body>
</html>
