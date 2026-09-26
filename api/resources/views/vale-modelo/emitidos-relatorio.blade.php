<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 8pt; color: #222; }
        h1 { font-size: 16pt; margin: 0 0 5mm; }
        .filtros { color: #555; margin-bottom: 4mm; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border-bottom: 0.2mm solid #ccc; padding: 1.8mm 1.2mm; vertical-align: top; }
        th { background: #eee; text-align: left; font-weight: bold; }
        .caption { color: #666; font-family: sans-serif; font-size: 7pt; font-weight: normal; }
        .caption-link { color: #666 !important; font-family: sans-serif !important; font-size: 7pt !important; font-weight: normal !important; text-decoration: none !important; }
        .right { text-align: right; }
        .link { color: #333; text-decoration: none; }
    </style>
</head>
<body>
<h1>Vales Compras Emitidos</h1>
<div class="filtros">
    Situação: {{ ucfirst($filtros['situacao'] ?? 'ativo') }}
    @if(!empty($filtros['de'])) · De {{ \Carbon\Carbon::parse($filtros['de'])->format('d/m/Y') }} @endif
    @if(!empty($filtros['ate'])) · Até {{ \Carbon\Carbon::parse($filtros['ate'])->format('d/m/Y') }} @endif
    @if(!empty($filtros['codnegocio'])) · Negócio #{{ str_pad($filtros['codnegocio'], 8, '0', STR_PAD_LEFT) }} @endif
    @if(isset($filtros['valorde']) && $filtros['valorde'] !== '') · Valor de R$ {{ number_format($filtros['valorde'], 2, ',', '.') }} @endif
    @if(isset($filtros['valorate']) && $filtros['valorate'] !== '') · Até R$ {{ number_format($filtros['valorate'], 2, ',', '.') }} @endif
    @if(!empty($filtros['busca'])) · Busca: {{ $filtros['busca'] }} @endif
</div>
<table>
    <thead>
    <tr>
        <th style="width: 11%">Data</th>
        <th class="right" style="width: 9%">Valor</th>
        <th class="right" style="width: 11%">Saldo</th>
        <th style="width: 22%">Nome</th>
        <th style="width: 37%">Favorecido</th>
        <th style="width: 10%"></th>
    </tr>
    </thead>
    <tbody>
    @foreach($vales as $vale)
        <tr>
            <td>
                <div>{{ \Carbon\Carbon::parse($vale->lancamento ?: $vale->criacao)->format('d/m/Y') }}</div>
                <div class="caption">{{ \Carbon\Carbon::parse($vale->lancamento ?: $vale->criacao)->format('H:i:s') }}</div>
            </td>
            <td class="right">
                <div>{{ number_format($vale->valorvale, 2, ',', '.') }}</div>
                <div class="caption">
                    <a class="caption-link" href="{{ rtrim(config('mg.apps.negocios'), '/') }}/negocio/{{ $vale->codnegocio }}">#{{ str_pad($vale->codnegocio, 8, '0', STR_PAD_LEFT) }}</a>
                </div>
            </td>
            <td class="right">
                @if($vale->titulo_codtitulo)
                    <a class="link" href="{{ rtrim(config('mg.apps.contas'), '/') }}/titulo/{{ $vale->titulo_codtitulo }}">
                        <div>{{ number_format(abs((float) $vale->titulo_saldo), 2, ',', '.') }}</div>
                    </a>
                    <div class="caption">
                        <a class="caption-link" href="{{ rtrim(config('mg.apps.contas'), '/') }}/titulo/{{ $vale->titulo_codtitulo }}">{{ $vale->titulo_numero }}</a>
                    </div>
                @else
                    —
                @endif
            </td>
            <td>
                <div>{{ $vale->aluno ?: '—' }}</div>
                @if($vale->turma)<div class="caption">{{ $vale->turma }}</div>@endif
            </td>
            <td>
                <div>{{ $vale->favorecido ?: 'Ao portador' }}</div>
                @if($vale->modelo)<div class="caption">{{ $vale->modelo }}</div>@endif
            </td>
            <td>
                {{ $vale->codnegociostatus == 3 || $vale->inativo ? 'Cancelado' : 'Ativo' }}
            </td>
        </tr>
    @endforeach
    </tbody>
</table>
</body>
</html>
