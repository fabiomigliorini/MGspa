<?php

use Illuminate\Support\Carbon;
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0.5cm 0.5cm;
        }

        .quebrar-pagina {
            page-break-after: always;
        }

        .quebrar-pagina:last-child {
            page-break-after: avoid;
        }

        .page_number:before {
            content: "Página " counter(page);
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7pt;
            margin-top: 2.2cm;
            margin-bottom: 0.5cm;
            /* border: 1px solid blue; */
        }

        #header {
            position: fixed;
            left: 0cm;
            top: 0cm;
            right: 0cm;
            height: 2.2cm;
            background-color: black;
            color: white;
            /* background-color: orange; */
        }

        #footer {
            position: fixed;
            left: 0cm;
            top: 28.2cm;
            right: 0cm;
            height: 0.5cm;
            /* background-color: orange; */
            text-align: center;
            background-color: black;
            color: white;
        }
    </style>
</head>

<body>

    @foreach ($tits as $tit)

    <!-- CABECALHO -->
    <div id="header">
        <table style="height: 100%;">
            <tr>
                <td style=" width: 2.5cm; vertical-align:middle;">
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents('/opt/www/MGspa/laravel/public/MGPapelariaLogoSeloPretoBranco.jpeg')) }}" alt="Logo" border="0" style="width: 100%; ">
                </td>
                <td style="text-align: center; width: 4.5cm; vertical-align:middle;">
                    <b style="font-size: 14pt">{{ $tit->Filial->filial}}</b>
                    <br>
                    {{ $tit->Filial->Pessoa->telefone1}}
                    <br>
                    <!-- {{ $tit->Filial->Pessoa->pessoa}}
                    <br>
                    <br>
                    {{ formataCnpj($tit->Filial->Pessoa->cnpj) }}
                    <br> -->
                    <b>{{Carbon::now()->format('d/m/Y H:i:s')}}</b>
                    <br>
                    <b>{{formataCodigo($tit->codtitulo)}}</b>
                    <br>
                    <span class="page_number"></span>
                </td>
            </tr>
        </table>
    </div>


    <h2>
        Vale Compras
        {{$tit->numero}}
        <br>
        {{formataCodigo($tit->codtitulo)}}
    </h2>

    <!-- CLIENTE -->
    <div>
        @if($tit->codpessoa != 1)
        <b style="font-size: 12pt">
            {{ $tit->Pessoa->fantasia }}
        </b>
        <br>
        {{ formataCodigo($tit->codpessoa) }} -
        {{ formataCnpjCpf($tit->Pessoa->cnpj, $tit->Pessoa->fisica) }}
        <br>
        {{ $tit->Pessoa->pessoa }}
        <br>
        {{ $tit->Pessoa->telefone1 }}
        <br>
        @if (!empty($tit->Pessoa->telefone2))
        {{ $tit->Pessoa->telefone2 }}
        <br>
        @endif
        @if (!empty($tit->Pessoa->telefone3))
        {{ $tit->Pessoa->telefone3 }}
        <br>
        @endif
        {{ $tit->Pessoa->endereco }},
        {{ $tit->Pessoa->numero }} -
        @if (!empty($tit->Pessoa->complemento))
        {{ $tit->Pessoa->complemento }} -
        @endif
        {{ $tit->Pessoa->Cidade->cidade }}/{{ $tit->Pessoa->Cidade->Estado->sigla }}
        @endif
        @if(!empty($tit->codpessoavendedor))
        <br> Vendedor: {{ $tit->PessoaVendedor->fantasia }}
        @endif
        @if(!empty($tit->Usuario->codpessoa))
        <br> Caixa: {{ $tit->Usuario->Pessoa->fantasia }}
        @endif
    </div>

    <br>
    <div>
        <b style="font-size: 10pt">
            Valor: R$ {{ formataNumero(abs($tit->saldo))}} ({{ formataValorPorExtenso(abs($tit->saldo)) }}).
        </b>
        <br><br>

        Sinop/MT, {{formataDataPorExtenso($tit->emissao)}}.
    </div>

    <br><br><br><br><br><br>
    <div style="margin-bottom: 0.2cm; border-bottom: 0.9px dashed black;">
    </div>
    <h3 style="text-align:center;">Migliorini & Migliorini Ltda</h3>


    <br><br>
    <div style="text-align:center;">
        <img src="data:image/png;base64,'<?php echo $barcodes[$tit->codtitulo] ?>'"> <br>
        Vale #{{str_pad($tit->codtitulo, 8, '0', STR_PAD_LEFT);}}
    </div>
    <br>
    <div class="quebrar-pagina"></div>
    @endforeach


</body>

</html>