<?php

use Illuminate\Support\Carbon;
?>
<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
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

    @foreach ($comprovantes as $comp)
    @php($tit = $comp['titulo'])
    @php($vale = $comp['vale'])

    <!-- CABECALHO -->
    <div id="header">
        <table style="height: 100%;">
            <tr>
                <td style=" width: 2.5cm; vertical-align:middle;">
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents(public_path('MGPapelariaLogoSeloPretoBranco.jpeg'))) }}" alt="Logo" border="0" style="width: 100%; ">
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
                    <!-- o numero do vale NAO cabe aqui: o #header e'
                         position:fixed e o Dompdf guarda so' a ultima
                         definicao dele, entao num negocio com dois vales a
                         pagina 1 sairia carimbada com o codigo do vale 2.
                         O numero ja' aparece logo abaixo, no corpo, que e'
                         onde ele e' de fato por vale. -->
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

    <!-- VALE VENDIDO NESTE NEGOCIO: escola, aluno, turma e a lista do kit.
         E' com esta lista que a familia retira o material. -->
    @if($vale)
    <div>
        @if($vale->codpessoafavorecido != 1)
        <b style="font-size: 12pt">{{ $vale->PessoaFavorecido->fantasia }}</b>
        <br>
        @else
        <b style="font-size: 12pt">Ao portador</b>
        <br>
        @endif
        @if(!empty($vale->aluno))
        Aluno: {{ $vale->aluno }}
        <br>
        @endif
        @if(!empty($vale->turma))
        Turma: {{ $vale->turma }}
        <br>
        @endif
        @if(!empty($vale->validade))
        Validade: {{ formataData($vale->validade) }}
        <br>
        @endif
    </div>

    @php($itens = $vale->NegocioValeProdutoBarraS->whereNull('inativo'))
    @if($itens->count())
    <br>
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td colspan="2" style="border-bottom: 0.9px solid black;"><b>Produtos</b></td>
            <td style="border-bottom: 0.9px solid black; text-align: right;"><b>Qtde</b></td>
        </tr>
        @foreach($itens as $item)
        <tr>
            <td style="width: 0.4cm; vertical-align: top;">{{ $loop->iteration }}</td>
            <td style="vertical-align: top;">
                {{ $item->ProdutoBarra->Produto->produto }}
                @if(!empty($item->ProdutoBarra->ProdutoVariacao->variacao))
                {{ $item->ProdutoBarra->ProdutoVariacao->variacao }}
                @endif
                <br>
                <span style="font-size: 6pt;">{{ $item->ProdutoBarra->barras }}</span>
            </td>
            <td style="vertical-align: top; text-align: right;">{{ formataNumero($item->quantidade, 0) }}</td>
        </tr>
        @endforeach
    </table>
    @endif

    @if($vale->valoravulso > 0)
    <br>
    <div>Valor avulso: R$ {{ formataNumero($vale->valoravulso) }}</div>
    @endif
    @endif

    <!-- CLIENTE -->
    <div>
        @if(!$vale && $tit->codpessoa != 1)
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
        @if(!$vale && !empty($tit->codpessoavendedor))
        <br> Vendedor: {{ $tit->PessoaVendedor->fantasia }}
        @endif
        @if(!$vale && !empty($tit->Usuario->codpessoa))
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