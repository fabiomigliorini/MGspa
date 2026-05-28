<!DOCTYPE html>
<html>

<head>
    <title>Titulos Abertos MG Papelaria</title>
</head>
<style>
    @page {
        margin: 5mm 5mm;
    }

    .page_number:before {
        content: "Página " counter(page);
    }

    html {
        /* background-color: lightgrey; */
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 8pt;
        width: 187mm;
        margin-top: 20mm;
        margin-bottom: 5mm;
        margin-right: auto;
        margin-left: auto;
    }

    th,
    td {
        border: 0.2mm solid;
        padding: 1mm;
    }

    th {
        text-align: left;
    }

    tbody tr:nth-child(odd) {
        background: #E5E5E5
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    .text-right {
        text-align: right
    }

    .text-center {
        text-align: center
    }

    .observacoes {
        white-space: pre-wrap;
        margin-top: 2mm;
    }

    #header {
        width: 187mm;
        position: fixed;
        top: 5mm;
        border-top: 0.2mm solid black;
        border-bottom: 0.2mm solid black;
        padding-left: 0mm;
        padding-right: 0mm;
    }

    #footer {
        width: 187mm;
        position: fixed;
        bottom: 5mm;
        height: 0mm;
        border-top: 0.2mm solid black;
        text-align: center;
    }

    .page-break {
        page-break-after: always;
    }

    .page-break:last-child {
        page-break-after: avoid;
    }
</style>

<body>

    <!-- CABECALHO -->
    <div id="header">
        <table style="height: 10mm; width: 100%">
            <thead>
                <tr>
                    <td style="width: 53mm; border: none !important; padding:0mm">
                        <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents('/opt/www/MGspa/laravel/public/MGPapelariaLogoPretoBranco.jpeg')) }}"
                            alt="Logo" border="0" style="height: 10mm; ">
                    </td>
                    <td style="width: 106mm; border: none !important; padding:0mm" class="text-center">
                        <h2>Posição de Títulos em Aberto</h2>
                    </td>
                    <td style="width: 28mm; border: none !important; padding:0mm" class="text-right">
                        <div class="page_number"></div>
                        <div>{{ date('d/m/Y H:i:s') }}</div>
                    </td>

                </tr>

            </thead>

        </table>
    </div>

    <div id="footer">
    </div>


    @foreach ($pessoas as $p)
        <h1>{{ $p->fantasia }}</h1>
        <div>
            {{ formataCodigo($p->codpessoa) }}
            |
            {{ $p->pessoa }}
            |
            @if ($p->fisica)
                CPF
            @else
                CNPJ
            @endif
            {{ formataCnpjCpf($p->cnpj, $p->fisica) }}
            |
            Total em Aberto
            <b>
                R$
                {{ formataNumero($p->saldo) }}
            </b>
        </div>
        @if ($p->agrupamentos->count() > 0)
            @include('titulo-relatorio.agrupamentos', [
                'agrupamentos' => $p->agrupamentos,
            ])
        @endif
        @if ($p->negocios->count() > 0)
            @include('titulo-relatorio.negocios', ['negocios' => $p->negocios])
        @endif
        @if ($p->titulos->count() > 0)
            @include('titulo-relatorio.titulos', ['titulos' => $p->titulos])
        @endif
        <div class="page-break"></div>
    @endforeach
</body>

</html>
