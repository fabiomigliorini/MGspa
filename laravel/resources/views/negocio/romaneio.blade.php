<!DOCTYPE html>
<html>

<head>
    <style>
        @page {
            margin: 0.5cm 0.5cm;
        }

        .page_number:before {
            content: "Página " counter(page);
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8pt;
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

    <!-- CABECALHO -->
    <div id="header">
        <table style="height: 100%;">
            <tr>
                <td style=" width: 2.5cm; vertical-align:middle;">
                    <img src="data:image/jpeg;base64,{{ base64_encode(file_get_contents('/opt/www/MGspa/laravel/public/MGPapelariaLogoSeloPretoBranco.jpeg')) }}"
                        alt="Logo" border="0" style="width: 100%; ">
                </td>
                <td style="text-align: center; width: 4.5cm; vertical-align:middle;">
                    <b style="font-size: 14pt">{{ $negocio->Filial->filial }}</b>
                    <br>
                    {{ $negocio->Filial->Pessoa->telefone1 }}
                    <br>
                    <!-- {{ $negocio->Filial->Pessoa->pessoa }}
                    <br>
                    <br>
                    {{ formataCnpj($negocio->Filial->Pessoa->cnpj) }}
                    <br> -->
                    <b>{{ $negocio->lancamento->format('d/m/Y H:i:s') }}</b>
                    <br>
                    <b>{{ formataCodigo($negocio->codnegocio) }}</b>
                    <br>
                    <span class="page_number"></span>
                </td>
            </tr>
        </table>
    </div>

    <!-- RODAPE -->
    <!--
    <div id="footer">
        <b>{{ formataCodigo($negocio->codnegocio) }}</b>
        - <span class="page_number"></span>
    </div>
    -->

    <!-- NUMERO NEGOCIO -->
    <h2>
        Romaneio de Conferência <br>
        {{ formataCodigo($negocio->codnegocio) }}
        {{ $negocio->NaturezaOperacao->naturezaoperacao }}<br>
    </h2>

    <!-- CLIENTE -->
    <div>
        @if ($negocio->codpessoa != 1)
            <b style="font-size: 12pt">
                {{ $negocio->Pessoa->fantasia }}
            </b>
            <br>
            {{ formataCodigo($negocio->codpessoa) }} -
            {{ formataCnpjCpf($negocio->Pessoa->cnpj, $negocio->Pessoa->fisica) }}
            <br>
            {{ $negocio->Pessoa->pessoa }}
            <br>
            {{ $negocio->Pessoa->telefone1 }}
            <br>
            @if (!empty($negocio->Pessoa->telefone2))
                {{ $negocio->Pessoa->telefone2 }}
                <br>
            @endif
            @if (!empty($negocio->Pessoa->telefone3))
                {{ $negocio->Pessoa->telefone3 }}
                <br>
            @endif
            {{ $negocio->Pessoa->endereco }},
            {{ $negocio->Pessoa->numero }} -
            @if (!empty($negocio->Pessoa->complemento))
                {{ $negocio->Pessoa->complemento }} -
            @endif
            {{ $negocio->Pessoa->Cidade->cidade }}/{{ $negocio->Pessoa->Cidade->Estado->sigla }}
        @endif
        @if (!empty($negocio->codpessoavendedor))
            <br> Vendedor: {{ $negocio->PessoaVendedor->fantasia }}
        @endif
        @if (!empty($negocio->Usuario->codpessoa))
            <br> Caixa: {{ $negocio->Usuario->Pessoa->fantasia }}
        @endif
    </div>


    <!-- PRODUTOS -->
    <h2>Produtos</h2>
    @foreach ($negocio->NegocioProdutoBarraS()->whereNull('inativo')->get() as $npb)
        <div style="margin-bottom: 0.2cm; border-bottom: 0.5px dashed black; ">
            {{ $npb->ProdutoBarra->barras }} -
            <b>{{ $npb->ProdutoBarra->descricao }}</b><br>
            <div style="text-align: right;">
                {{ formataNumero($npb->quantidade, 3) }}
                {{ $npb->ProdutoBarra->UnidadeMedida->sigla }}
                de R$
                {{ formataNumero($npb->valorunitario, 2) }}
                = R$
                <b>
                    @if (!empty($npb->valorprodutos))
                        {{ formataNumero($npb->valorprodutos, 2) }}
                    @else
                        {{ formataNumero($npb->valortotal, 2) }}
                    @endif
                </b>
            </div>
        </div>
    @endforeach

    <!-- TOTAIS -->
    <div style="text-align: right">
        @if ($negocio->valortotal != $negocio->valorprodutos)

            <!-- PRODUTOS -->
            Produtos R$ {{ formataNumero($negocio->valorprodutos, 2) }} <br>

            <!-- DESCONTO -->
            @if (!empty($negocio->valordesconto))
                Desconto R$ {{ formataNumero($negocio->valordesconto, 2) }} <br>
            @endif

            <!-- FRETE -->
            @if (!empty($negocio->valorfrete))
                Frete R$ {{ formataNumero($negocio->valorfrete, 2) }} <br>
            @endif

            <!-- SEGURO -->
            @if (!empty($negocio->valorseguro))
                Seguro R$ {{ formataNumero($negocio->valorseguro, 2) }} <br>
            @endif

            <!-- OUTRAS -->
            @if (!empty($negocio->valoroutras))
                Outras R$ {{ formataNumero($negocio->valoroutras, 2) }} <br>
            @endif

        @endif

        <!-- TOTAL -->
        <b>Total R$ {{ formataNumero($negocio->valortotal, 2) }}</b>

        <!-- PAGAMENTOS -->
        @foreach ($negocio->NegocioFormaPagamentoS as $pag)
            <br>

            <!-- JUROS -->
            @if (!empty($pag->valorjuros))
                Juros
                R$ {{ formataNumero($pag->valorjuros, 2) }}
                <br>
            @endif

            <!-- FORMA PAGAMENTO -->
            {{ $pag->FormaPagamento->formapagamento }}
            R$ {{ formataNumero($pag->valorpagamento, 2) }}

            <!-- TROCO -->
            @if (!empty($pag->valortroco))
                <br>
                Troco
                R$ {{ formataNumero($pag->valortroco, 2) }}
            @endif
        @endforeach

    </div>

    @if (!empty($negocio->observacoes))
        <h2>Observacoes</h2>
        <span style="white-space: pre-line">{{ $negocio->observacoes }}</span>
    @endif

    <!-- TITULOS -->
    @foreach ($negocio->NegocioFormaPagamentos as $nfp)
        @foreach ($nfp->Titulos()->orderBy('vencimento')->get() as $titulo)
            <!-- CABECALHO VENCIMENTOS -->
            @if ($loop->first)
                <h1 style="page-break-before: always;">Confissão de Dívida</h1>
                <p style="font-size: larger; text-align:justify">
                    Confesso(amos) e me(nos) constituo(imos) devedor(es)
                    do(s) valor(es) descrito(s) abaixo, obrigando-me(nos) a pagar em moeda corrente
                    do pais, conforme os vencimento(s). Declaro(amos) ainda, ter recebido o(s)
                    serviço(s) e/ou produto(s) descritos no romaneio de conferência
                    <b>{{ formataCodigo($negocio->codnegocio) }}</b>
                    sem nada a reclamar.
                </p>
            @endif
            <p style="font-size: larger; text-align:justify">
                Pagarei em <b>{{ formataData($titulo->vencimento, 'd/m/Y') }}</b>
                @if (!empty($titulo->debito))
                    R$ <b>{{ formataNumero($titulo->debito) }}</b>
                @else
                    R$ <b>{{ formataNumero($titulo->credito) }}</b>
                @endif
                <br>
            </p>
            @if ($loop->last)
                <p style="font-size: larger; text-align:justify">
                    Totalizando
                    R$ <b>{{ formataNumero($nfp->valorpagamento) }}</b>
                    ({{ formataValorPorExtenso($nfp->valorpagamento) }})
                    .
                </p>
                <p style="font-size: larger; text-align:justify">
                    {{ $negocio->Filial->Pessoa->Cidade->cidade }}/{{ $negocio->Filial->Pessoa->Cidade->Estado->sigla }},
                    {{ $negocio->lancamento->format('d/m/Y') }}.
                </p>
                <div style="font-size: larger; margin-top: 2cm; border-top: 1px solid black">
                    {{ $negocio->Pessoa->pessoa }} <br>
                    @if ($negocio->Pessoa->fisica)
                        CPF
                    @else
                        CNPJ
                    @endif
                    {{ formataCnpjCpf($negocio->Pessoa->cnpj, $negocio->Pessoa->fisica) }}
                    ({{ formataCodigo($negocio->codpessoa) }})
                </div>
                <div style="font-size: larger; margin-top: 2cm; border-top: 1px solid black">
                    Nome Completo Legível
                </div>
                <p style="font-size: larger; margin-top: 0.5cm; font-size:">
                    Negócio <b>{{ formataCodigo($negocio->codnegocio) }}</b>
                    @if ($negocio->codpessoavendedor)
                        <br>
                        Vendedor <b>{{ $negocio->PessoaVendedor->fantasia }}</b>
                    @endif
                    <br>
                    Usuário <b>{{ $negocio->Usuario->usuario }}</b>
                    @if (@$negocio->Pdv->apelido)
                        <br>
                        PDV <b>{{ $negocio->Pdv->apelido }}</b>
                    @endif
                </p>
            @endif
        @endforeach
    @endforeach

</body>

</html>
