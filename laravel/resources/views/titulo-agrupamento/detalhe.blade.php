@php
    use Carbon\Carbon;

    $pessoa = $ag->Pessoa;
    $titulos = $ag->TituloS;
    $filial = optional($titulos->first())->Filial;
    $filialPessoa = optional($filial)->Pessoa;

    $valorTotal = (float)$ag->debito - (float)$ag->credito;
    $valorAbs = abs($valorTotal);
    $opTotal = $valorTotal < 0 ? 'CR' : 'DB';

    $tels = array_values(array_filter([
        $pessoa->telefone1 ?? null,
        $pessoa->telefone2 ?? null,
        $pessoa->telefone3 ?? null,
    ]));
    $telefonesStr = implode(' / ', $tels);

    $emi = $ag->emissao ? Carbon::parse($ag->emissao)->format('d/m/Y') : '';
    $obs = (string)$ag->observacao;

    $ufSigla = optional(optional(optional($pessoa)->Cidade)->Estado)->sigla;
@endphp

<style>
    body { font-family: helvetica; font-size: 9pt; color: #000; }
    table { width: 100%; border-collapse: collapse; }
    h3 { margin: 12px 0 4px; font-size: 11pt; }
    .label { color: #666; }
    .small { font-size: 7.5pt; color: #555; }
    .bloco-cliente td { padding: 1px 2px; vertical-align: top; }
    .divisor { border-top: 1px solid #000; margin: 6px 0 8px; }
    table.titulos thead th {
        border-bottom: 1px solid #000;
        text-align: left;
        font-weight: bold;
        font-size: 8pt;
        padding: 3px 4px;
    }
    table.titulos td {
        border-bottom: 1px solid #ddd;
        font-size: 8pt;
        padding: 3px 4px;
    }
    .declaracao { font-size: 9.5pt; line-height: 1.4; margin: 8px 0; text-align: justify; }
    .contatos td { font-size: 9pt; padding: 4px 2px; }
    .data-local { text-align: center; margin: 26px 0 0; font-size: 9pt; }
    .assinatura { text-align: center; margin-top: 32px; font-size: 9pt; }
    .assinatura .linha { letter-spacing: 1px; }
</style>

<table class="bloco-cliente">
    <tr>
        <td style="width: 60px"><span class="label">Cliente:</span></td>
        <td>
            <b style="font-size: 11pt">{{ $pessoa->fantasia }}</b>
            @if (!empty($telefonesStr))
                <b style="font-size: 11pt"> - {{ $telefonesStr }}</b>
            @endif
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="small">
            {{ formataCodigo($pessoa->codpessoa) }} -
            {{ $pessoa->fisica ? 'CPF' : 'CNPJ' }}: {{ formataCnpjCpf($pessoa->cnpj, $pessoa->fisica) }}
            @if (!empty($pessoa->ie) && !empty($ufSigla))
                - IE: {{ formataInscricaoEstadual($pessoa->ie, $ufSigla) }}
            @endif
            - {{ $pessoa->pessoa }}
        </td>
    </tr>
    @if ($filialPessoa)
        <tr>
            <td><span class="label">Filial:</span></td>
            <td>
                {{ $filialPessoa->fantasia }}
                ({{ $filialPessoa->pessoa }} - {{ formataCnpjCpf($filialPessoa->cnpj, $filialPessoa->fisica) }}){{ !empty($filialPessoa->telefone1) ? ' - ' . $filialPessoa->telefone1 : '' }}
            </td>
        </tr>
    @endif
    <tr>
        <td><span class="label">Emissão:</span></td>
        <td>
            {{ $emi }}
            <span style="margin-left: 24px"><span class="label">Total:</span> <b>{{ formataNumero($valorAbs) }} {{ $opTotal }}</b></span>
        </td>
    </tr>
    @if (!empty($obs))
        <tr>
            <td><span class="label">Obs.:</span></td>
            <td>{{ $obs }}</td>
        </tr>
    @endif
    @if ($ag->cancelamento)
        <tr>
            <td></td>
            <td style="color:#c00"><b>Estornado em {{ Carbon::parse($ag->cancelamento)->format('d/m/Y') }}</b></td>
        </tr>
    @endif
</table>

<div class="divisor"></div>

<h3>Vencimento(s) do Fechamento:</h3>
<table class="titulos">
    <thead>
        <tr>
            <th>Filial</th>
            <th>Número</th>
            <th style="text-align:center">Emissão</th>
            <th style="text-align:center">Vencimento</th>
            <th style="text-align:right">Valor</th>
            <th>Boleto</th>
            <th style="text-align:center">Agendado Para</th>
            <th style="text-align:center">Pago em</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($titulos as $t)
            @php
                $valorT = (float)$t->debito - (float)$t->credito;
                $opT = $valorT < 0 ? 'CR' : 'DB';
                $boletoStr = $t->boleto
                    ? trim((optional($t->Portador)->portador ?? '') . ' ' . (!empty($t->nossonumero) ? '- ' . $t->nossonumero : ''))
                    : 'Não';
            @endphp
            <tr>
                <td>{{ optional($t->Filial)->filial }}</td>
                <td>{{ $t->numero }}</td>
                <td style="text-align:center">{{ $t->emissao ? Carbon::parse($t->emissao)->format('d/m/Y') : '' }}</td>
                <td style="text-align:center">{{ $t->vencimento ? Carbon::parse($t->vencimento)->format('d/m/Y') : '' }}</td>
                <td style="text-align:right">{{ formataNumero(abs($valorT)) }} {{ $opT }}</td>
                <td>{{ $boletoStr }}</td>
                <td style="text-align:center">______/_____/_____</td>
                <td style="text-align:center">______/_____/_____</td>
            </tr>
        @endforeach
    </tbody>
</table>

<h3>Em substituição ao(s) seguinte(s) título(s):</h3>
<table class="titulos">
    <thead>
        <tr>
            <th>Filial</th>
            <th>Número</th>
            <th style="text-align:center">Emissão</th>
            <th style="text-align:center">Vencimento</th>
            <th style="text-align:right">Valor</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($ag->MovimentoTituloS as $m)
            @if (optional($m->TipoMovimentoTitulo)->codtipomovimentotitulo != 901) @continue @endif
            @if (!$m->Titulo) @continue @endif
            @php
                $valorM = (float)$m->debito - (float)$m->credito;
                // Inverte: na "em substituição" o título original (mov CR) aparece como DB
                // e o título gerado (mov DB) aparece como CR para o cliente
                $opM = $valorM < 0 ? 'DB' : 'CR';
                $vMabs = abs($valorM);
            @endphp
            <tr>
                <td>{{ optional($m->Titulo->Filial)->filial }}</td>
                <td>{{ $m->Titulo->numero }}</td>
                <td style="text-align:center">{{ $m->Titulo->emissao ? Carbon::parse($m->Titulo->emissao)->format('d/m/Y') : '' }}</td>
                <td style="text-align:center">{{ $m->Titulo->vencimento ? Carbon::parse($m->Titulo->vencimento)->format('d/m/Y') : '' }}</td>
                <td style="text-align:right">{{ formataNumero($vMabs) }} {{ $opM }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<div class="divisor"></div>

<div class="declaracao">
    Declaro(amos) ter conferido o(s) título(s) acima listado(s), que reconheço(emos) a exatidão da dívida de
    R$ {{ formataNumero($valorAbs) }}
    ({{ formataValorPorExtenso($valorAbs, true) }})
    expressa neste relatório e que pagarei(emos) a importância à
    {{ optional($filialPessoa)->pessoa }}, ou a sua ordem, no(s) vencimento(s) e/ou nos agendamento(s) acima indicado(s).
</div>

<div style="font-size: 9.5pt; margin: 8px 0;">
    Assinale abaixo os numeros de WhatsApp e E-Mails que você deseja receber os documentos de fechamento:
</div>

@php
    $emails = $pessoa->PessoaEmailS()->whereNull('inativo')->orderBy('ordem')->get();
    $telefones = $pessoa->PessoaTelefoneS()->whereNull('inativo')->orderBy('ordem')->get();
    $linhas = max($emails->count(), $telefones->count(), 3);
@endphp

<table class="contatos">
    @for ($i = 0; $i < $linhas; $i++)
        <tr>
            <td style="width: 50%">
                @if (isset($emails[$i]))
                    (    )&nbsp;&nbsp;{{ $emails[$i]->email }}
                @else
                    (    )&nbsp;&nbsp;_________________________________________
                @endif
            </td>
            <td style="width: 50%">
                @if (isset($telefones[$i]))
                    @php
                        $tel = $telefones[$i];
                        if ($tel->tipo == 1) {
                            $fmt = str_pad($tel->telefone, 8, ' ', STR_PAD_LEFT);
                            $fmt = substr($fmt, 0, 4) . '-' . substr($fmt, 4);
                        } else {
                            $fmt = str_pad($tel->telefone, 9, ' ', STR_PAD_LEFT);
                            $fmt = substr($fmt, 0, 1) . '-' . substr($fmt, 1, 4) . '-' . substr($fmt, 5);
                        }
                    @endphp
                    (    )&nbsp;&nbsp;+{{ (int)$tel->pais }} ({{ (int)$tel->ddd }}) {{ $fmt }}
                @else
                    (    )&nbsp;&nbsp;_________________________________________
                @endif
            </td>
        </tr>
    @endfor
</table>

<div class="data-local">
    ________________________,____ de _________________ de ______.
</div>

<div class="assinatura">
    <div class="linha">__________________________________________________________</div>
    <div>{{ $pessoa->pessoa }}</div>
    <div>
        {{ $pessoa->fisica ? 'CPF' : 'CNPJ' }}: {{ formataCnpjCpf($pessoa->cnpj, $pessoa->fisica) }}
    </div>
</div>
