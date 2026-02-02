<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha do Colaborador</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            padding: 20px;
        }

        h1 {
            text-align: center;
            font-size: 14pt;
            margin-bottom: 15px;
            text-decoration: underline;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        td {
            border: 1px solid #000;
            padding: 5px;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            background-color: #f0f0f0;
        }

        .checkbox {
            display: inline-block;
            margin-right: 10px;
        }

        .checkbox-group {
            padding: 5px;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 20px;
            font-size: 9pt;
            font-style: italic;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-top: 40px;
            padding-top: 5px;
            text-align: center;
        }

        .page-break {
            page-break-after: always;
        }

        .highlight {
            background-color: #ffeb3b;
        }
    </style>
</head>

<body>

    {{-- <div class="margin-left: 100px"> --}}
    <div style="width: 90%; margin: 80px auto 0;">

        <!-- PÁGINA 1: FICHA DO COLABORADOR -->
        <h1 style=" margin-bottom: 50px">FICHA DO COLABORADOR</h1>

        <table class="width: 60%">
            <tr>
                <td class="label">NOME</td>
                <td colspan="3">{{ $pessoa->pessoa ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">CPF</td>
                <td colspan="3">{{ formataCnpjCpf($pessoa->cnpj, $pessoa->fisica) }}</td>
            </tr>
            <tr>
                <td class="label">MÃE</td>
                <td colspan="3">{{ $pessoa->mae ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">PAI</td>
                <td colspan="3">{{ $pessoa->pai ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">RG</td>
                <td colspan=3>Nº: {{ $pessoa->rg ?? '' }}</td>
            </tr>
            <tr>
                <td class="label">TITULO ELEITOR</td>
                <td>Nº {{ $pessoa->tituloeleitor ? formataNumero($pessoa->tituloeleitor, 0) : '' }}</td>
                <td>ZONA: {{ $pessoa->titulozona ? formataNumero($pessoa->titulozona, 0) : '' }}</td>
                <td>SEÇÃO: {{ $pessoa->titulosecao ? formataNumero($pessoa->titulosecao, 0) : '' }}</td>
            </tr>
            <tr>
                <td class="label">PIS/PASEP</td>
                <td colspan="3">{{ $pessoa->pispasep ? formataNumero($pessoa->pispasep, 0) : '' }}</td>
            </tr>
            <tr>
                <td class="label">CARTEIRA DE TRABALHO</td>
                <td>Nº {{ $pessoa->ctps ?? '' }}</td>
                <td>SÉRIE {{ $pessoa->seriectps ?? '' }}/{{ $pessoa->EstadoCtps->sigla ?? '' }}</td>
                <td>EXPEDIÇÃO {{ $pessoa->emissaoctps ? $pessoa->emissaoctps->format('d/m/Y') : '' }}</td>
            </tr>
            <tr>
                <td class="label">ESTADO CIVIL</td>
                <td colspan="3">
                    {{ $pessoa->EstadoCivil?->estadocivil }}
                </td>
            </tr>
            <tr>
                <td class="label">GRAU DE INSTRUÇÃO</td>
                <td colspan="3">
                    {{ $pessoa->GrauInstrucao?->grauinstrucao }}
                </td>
            </tr>
            <tr>
                <td class="label">NASCIMENTO</td>
                <td>DATA: {{ $pessoa->nascimento ? $pessoa->nascimento->format('d/m/Y') : '' }}</td>
                <td colspan="2">MUNICIPIO: {{ $pessoa->CidadeNascimento->cidade ?? '' }} /
                    {{ $pessoa->CidadeNascimento->Estado->sigla ?? '' }}</td>
            </tr>
            @foreach ($pessoa->PessoaEnderecoS()->orderBy('ordem')->get() as $pe)
                <tr>
                    <td class="label">ENDEREÇO</td>
                    <td colspan="3">
                        {{ $pe->endereco ?? '' }},
                        {{ $pe->numero ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">COMPLEMENTO</td>
                    <td colspan="3">
                        {{ $pe->complemento ?? '' }}
                    </td>
                </tr>
                <tr>
                    <td class="label">BAIRRO</td>
                    <td colspan="3">{{ $pe->bairro ?? '' }}</td>
                </tr>
                <tr>
                    <td class="label">MUNICÍPIO</td>
                    <td colspan="2">
                        {{ $pe->Cidade->cidade ?? '' }} /
                        {{ $pe->Cidade->Estado->sigla ?? '' }}
                    </td>
                    <td>CEP: {{ formataCep($pe->cep) ?? '' }}</td>
                </tr>
            @endforeach
            {{-- 
        <tr>
            <td class="label">POSSUI DEPENDENTES?</td>
            <td colspan="3">Anexar certidão nasc., Declaração escolar, Cart. de vacinação e <strong
                    class="highlight">CPF</strong></td>
        </tr> 
        --}}
            <tr>
                <td class="label">TELEFONE/CONTATO:</td>
                <td colspan="3">
                    @foreach ($pessoa->PessoaTelefoneS()->orderBy('ordem')->get() as $pt)
                        ({{ $pt->ddd }})
                        {{ formataPorMascara($pt->telefone, '#-####-####') }}
                    @endforeach
                </td>
            </tr>
            <tr>
                <td class="label">E-MAIL:</td>
                <td colspan="3">
                    @foreach ($pessoa->PessoaEmailS()->orderBy('ordem')->get() as $pe)
                        {{ $pe->email }}
                    @endforeach
                </td>
            </tr>
            {{-- 
        <tr>
            <td class="label">TRABALHA EM OUTRA<br>EMPRESA?</td>
            <td colspan="3" class="checkbox-group">
                <span class="checkbox">( &nbsp; )SIM</span>
                <span class="checkbox">( &nbsp; )NÃO</span>
            </td>
        </tr> 
        --}}
        </table>

        @php
            $cargoAtual = $colaborador->ColaboradorCargoS->first();
        @endphp

        <p>
            <strong>Data de admissão:</strong>
            {{ $colaborador->contratacao ? $colaborador->contratacao->format('d/m/Y') : '' }}
        </p>
        <p>
            <strong>Cargo:</strong>
            {{ $cargoAtual->Cargo->cargo ?? '' }}
        </p>
        <p>
            <strong>Salário:</strong>
            {{ $cargoAtual && $cargoAtual->Cargo ? 'R$ ' . number_format($cargoAtual->salario ?? 0, 2, ',', '.') : '' }}
            {{-- <strong>Horário:</strong> --}}
        </p>
        <p>
            <strong>Contrato Experiência:</strong>
            {{ $colaborador->experiencia ? \Carbon\Carbon::parse($colaborador->contratacao)->diffInDays(\Carbon\Carbon::parse($colaborador->experiencia)) + 1 : '____' }}
            Dias
        </p>
    </div>
    <div class="page-break"></div>

    <!-- PÁGINA 2: DECLARAÇÃO DE RAÇA/ETNIA -->
    <h1 style="margin-bottom: 75px; margin-top: 75px">DECLARAÇÃO DE RAÇA/ETNIA (Lei 14553/2023)</h1>

    <div style="width: 70%; margin: 80px auto 0;">
        <p style="margin-bottom: 40px">
            EU
            <u>{{ $pessoa->pessoa ?? '' }}</u>
            declaro minha raça/cor/etnia:
        </p>

        @foreach (Mg\Pessoa\Etnia::whereNull('inativo')->orderBy('etnia')->get() as $etnia)
            <p style="margin-bottom: 10px">
                @if ($etnia->codetnia == $pessoa->codetnia)
                    (__X__)
                @else
                    (_____)
                @endif
                {{ $etnia->etnia }}
            </p>
        @endforeach

        <div style="margin-top: 80px" class="signature-line">
            {{ $pessoa->pessoa ?? '' }} <br>
            {{ formataCpf($pessoa->cnpj) }} <br>
        </div>
    </div>
</body>

</html>
