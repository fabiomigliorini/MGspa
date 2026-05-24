<?php
$tits = collect($ta->TituloS->sortBy('vencimento')->sortBy('numero')->all());
?>
<!DOCTYPE html>
<html>

<head>
    <title>Agrupamento MG Papelaria</title>
</head>
<style>
    body {
        font-family: Roboto, Helvetica, Arial, sans-serif;
    }

    th,
    td {
        text-align: center;
        border: 1px solid;
        padding: 4px;
    }

    table {
        border-collapse: collapse;
    }
</style>

<body>
    <div>
        <img src="{{ $message->embed('/opt/www/MGspa/laravel/public/MailNfeCabecalho.jpeg') }}" style="max-width:100%"> <br />
    </div>

    <h2>Olá {{ $ta->Pessoa->fantasia }},</h2>

    <p>
        Segue em anexo os documentos referente ao agrupamento de títulos
        <b>{{ formataCodigo($ta->codtituloagrupamento) }}</b>
        emitido em
        <b>{{ $ta->emissao->formatLocalized('%d de %B de %Y (%A)') }}</b>.
    </p>

    <p>
        @if (sizeof($tits) > 1)
            Os títulos gerados tem o(s) seguinte(s) vencimento(s):
        @else
            O títulos gerados tem o seguinte vencimento:
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>
                    Número
                </th>
                <th>
                    Vencimento
                </th>
                <th>
                    Valor
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($tits as $tit)
                <tr>
                    <td>
                        {{ $tit->numero }}
                    </td>
                    <td>
                        {{ formataData($tit->vencimento) }}

                    </td>
                    <td>
                        {{ formataNumero($tit->debito) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if (sizeof($tits) > 1)
            <tfoot>
                <tr>
                    <th colspan="2">
                        Total
                    </th>
                    <th>
                        {{ formataNumero($tits->sum('debito')) }}
                    </th>
                </tr>
            </tfoot>
        @endif
    </table>

    <p>
        @if (sizeof($baixas) > 1)
            Em substituição aos seguintes títulos:
        @else
            Em substituição ao seguinte título:
        @endif
    </p>

    <table>
        <thead>
            <tr>
                <th>
                    Número
                </th>
                <th>
                    Emissão
                </th>
                <th>
                    Vencimento
                </th>
                <th>
                    Original
                </th>
                <th>
                    Agrupado
                </th>
                <th>
                    Multa
                </th>
                <th>
                    Juros
                </th>
                <th>
                    Desconto
                </th>
                <th>
                    Outras
                </th>
                <th>
                    Total
                </th>
            </tr>
        </thead>
        <tbody>
            @foreach ($baixas as $baixa)
                <tr>
                    <td>
                        {{ $baixa->numero }}
                    </td>
                    <td>
                        {{ formataData($baixa->emissao) }}
                    </td>
                    <td>
                        {{ formataData($baixa->vencimento) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->valor) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->principal) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->multa) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->juros) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->desconto) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->outras) }}
                    </td>
                    <td>
                        {{ formataNumero($baixa->total) }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        @if (sizeof($tits) >= 1)
            <tfoot>
                <tr>
                    <th colspan="4">
                        Total
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('principal')) }}
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('multa')) }}
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('juros')) }}
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('desconto')) }}
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('outras')) }}
                    </th>
                    <th>
                        {{ formataNumero($baixas->sum('total')) }}
                    </th>
                </tr>
            </tfoot>
        @endif
    </table>

    <p>
        Esta <b>mensagem é gerada automaticamente</b> pelo nosso sistema, qualquer dúvida ou erro entre em contato que teremos o maior prazer em lhe atender.
    </p>

    <p>
        Atenciosamente,
    </p>
    <div>
        Departamento de Cobrança
    </div>
    <div>
        <b style="background-color:rgb(255,255,0)">
            <font color="#ff0000">&nbsp;MG</font>&nbsp;<font color="#0000ff">Papelaria&nbsp;</font>
        </b>
    </div>
    <div>
        <a href="mailto:cobranca@mgpapelaria.com.br" target="_blank">cobranca@mgpapelaria.com.br</a>
    </div>
    <div>
        <a href="tel:+556635327678">(66)3532-7678 - Fixo</a>
    </div>
    <div>
        <a href="tel:+556699370555">(66)9-9937-0555 - Celular</a>
    </div>
    <div>
        <a href="https://wa.me/556699370555">(66)9-9937-0555 - WhatsApp</a>
    </div>
    <div>
        <font face="arial, helvetica, sans-serif"><a href="http://www.mgpapelaria.com.br/"
                target="_blank">www.mgpapelaria.com.br</a></font>
    </div>
    <div>
        <font face="arial, helvetica, sans-serif"><a href="https://facebook.com/MGPapelaria"
                target="_blank">facebook.com/MGPapelaria</a></font>
    </div>
    <div>
        <font face="arial, helvetica, sans-serif"><a href="https://instagram.com/MGPapelaria"
                target="_blank">instagram.com/MGPapelaria</a></font>
    </div>
</body>

</html>
