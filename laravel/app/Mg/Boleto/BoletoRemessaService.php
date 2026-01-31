<?php

namespace Mg\Boleto;

use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Sequence\SequenceService;

class BoletoRemessaService
{

    public static function arquivarRemessa($codportador, $arquivo)
    {
        $portador = Portador::findOrFail($codportador);
        $info = pathinfo($arquivo);
        $origem = "{$portador->conta}-{$portador->contadigito}/Remessa/{$info['basename']}";
        $ano = Carbon::now()->format('Y');
        $destino = "{$portador->conta}-{$portador->contadigito}/Remessa/Enviadas/{$ano}/{$info['basename']}";
        if (Storage::disk('boleto')->exists($destino)) {
            Storage::disk('boleto')->delete($destino);
        }
        return Storage::disk('boleto')->move($origem, $destino);
    }

    public static function gerarRemessa($codportador, $codtitulo)
    {
        // Busca portador e titulos
        $portador = Portador::findOrFail($codportador);
        $titulos = $portador->TituloS()->whereIn('codtitulo', $codtitulo)->whereNull('remessa')->get();

        // Valida existencia de titulos para remessa
        if ($titulos->count() <= 0) {
            throw new \Exception("Não foi localizado nenhum titulo para compor a remessa!", 1);
        }

        // associa o numero de remessa aos titulos
        DB::beginTransaction();
        $remessa = SequenceService::incrementa("tbltitulo_remessa_{$portador->codportador}_seq");
        foreach ($titulos as $titulo) {
            $titulo->remessa = $remessa;
            $titulo->save();
        }
        DB::commit();

        // Gera o arquivo
        if (!$arquivo = static::gerarArquivoRemessa($portador, $remessa)) {
            throw new \Exception("Falha ao gerar o arquivo da remessa {$remessa}!", 1);
        }

        // retorna resultado
        return [
            'remessa' => $remessa,
            'titulos' => $titulos->count(),
            'arquivo' => $arquivo
        ];
    }

    public static function gerarArquivoRemessa($portador, $remessa)
    {
        // monta o nome do arquivo de remessa
        $data = Carbon::now();
        $dia = $data->format('dm');
        $rem = str_pad($remessa, 2, '0', STR_PAD_LEFT);
        $rem = substr($rem, -2);
        $arquivo = "{$portador->conta}-{$portador->contadigito}/Remessa/CB{$dia}{$rem}.REM";

        // registro 0 - Header
        $iLinha = 1;
        $linha = '0';
        $linha .= '1';
        $linha .= 'REMESSA';
        $linha .= '01';
        $linha .= str_pad('COBRANCA', 15, ' ', STR_PAD_RIGHT);
        $linha .= str_pad($portador->convenio, 20, '0', STR_PAD_LEFT);
        $linha .= str_pad(strtoupper($portador->Filial->Pessoa->pessoa), 30, ' ', STR_PAD_RIGHT);
        $linha .= str_pad($portador->codbanco, 3, '0', STR_PAD_LEFT);
        $linha .= str_pad('BRADESCO', 15, ' ', STR_PAD_RIGHT);
        $linha .= $data->format('dmy');
        $linha .= str_pad('', 8, ' ', STR_PAD_RIGHT);
        $linha .= 'MX';
        $linha .= str_pad($remessa, 7, '0', STR_PAD_LEFT);
        $linha .= str_pad('', 277, ' ', STR_PAD_RIGHT);
        $linha .= str_pad($iLinha, 6, '0', STR_PAD_LEFT);
        $linhas = [$linha];

        // registro 1 - Titulos
        $titulos = $portador->TituloS()->with('Pessoa')->where('remessa', $remessa)->orderBy('codtitulo')->get();
        foreach ($titulos as $titulo) {
            $iLinha++;

            // 001 a 001 Identificação do Registro
            $linha = '1';

            // 002 a 006 Agência de Débito (opcional)
            $linha .= str_pad('', 5, '0', STR_PAD_LEFT);

            // 007 a 007 Dígito da Agência de Débito (opcional)
            $linha .= ' ';

            // 008 a 012 Razão da Conta Corrente (opcional)
            $linha .= str_pad('', 5, '0', STR_PAD_LEFT);

            // 013 a 019 Conta Corrente (opcional)
            $linha .= str_pad('', 7, '0', STR_PAD_LEFT);

            // 020 a 020 Dígito da Conta Corrente (opcional)
            $linha .= ' ';

            // 021 a 037 Identificação da Empresa Beneficiária no Banco
            // Zero, Carteira, Agência e Conta - Corrente Vide Obs.
            $linha .= '0';
            $linha .= str_pad($portador->carteira, 3, '0', STR_PAD_LEFT);
            $linha .= str_pad($portador->agencia, 5, '0', STR_PAD_LEFT);
            $linha .= str_pad($portador->conta, 7, '0', STR_PAD_LEFT);
            $linha .= str_pad($portador->contadigito, 1, '0', STR_PAD_LEFT);

            // 038 a 062 No Controle do Participante
            $linha .= str_pad($titulo->numero, 25, ' ', STR_PAD_RIGHT);

            // 063 a 065 Código do Banco a ser debitado na Câmara de Compensação
            $linha .= '000';

            // 066 a 066 Campo de Multa
            $linha .= '2';

            // 067 a 070 Percentual de multa
            $linha .= str_pad(2 * 100, 4, '0', STR_PAD_LEFT);

            // 071 a 081 Identificação do Título no Banco
            $linha .= str_pad($titulo->nossonumero, 11, '0', STR_PAD_LEFT);

            // 082 a 082 Digito de Auto Conferencia do Número Bancário.
            $linha .= str_pad(static::calculaDVNossoNumeroBradesco($portador->carteira . $titulo->nossonumero), 1, '0', STR_PAD_LEFT);

            // 083 a 092 Desconto Bonificação por dia
            $linha .= str_pad('', 10, '0', STR_PAD_LEFT);

            // 093 a 093 Condição para Emissão da Papeleta de Cobrança
            // 2 = Cliente emite e o Banco somente processa o registro
            $linha .= '2';

            // 094 a 094 Ident. se emite Boleto para Débito Automatico
            $linha .= ' ';

            // 095 a 104 Identificação da Operação do Banco
            $linha .= str_pad('', 10, ' ', STR_PAD_RIGHT);

            // 105 a 105 Indicador Rateio Crédito (opcional)
            $linha .= ' ';

            // 106 a 106 Endereçamento para Aviso do Débito Automático em Conta Corrente (opcional)
            $linha .= '0';

            // 107 a 108 Quantidade de pagamentos
            $linha .= '  ';

            // 109 a 110 Identificação da ocorrência
            $linha .= '01';

            // 111 a 120 No do Documento
            $linha .= substr(str_pad($titulo->numero, 10, ' ', STR_PAD_RIGHT), 0, 10);

            // 121 a 126 Data do Vencimento do Título
            $linha .= $titulo->vencimento->format('dmy');

            // 127 a 139 Valor do Título
            $linha .= str_pad($titulo->saldo * 100, 13, '0', STR_PAD_LEFT);

            // 140 a 142 Banco Encarregado da Cobrança
            $linha .= '000';

            // 143 a 147 Agência Depositária
            $linha .= str_pad('', 5, '0', STR_PAD_LEFT);

            // 148 a 149 Espécie de Título - Duplicata
            $linha .= '01';

            // 150 a 150 Identificação
            $linha .= 'A';

            // 151 a 156 Data da emissão do Título
            $linha .= $titulo->emissao->format('dmy');

            // 157 a 158 1a instrução
            $linha .= '00';

            // 159 a 160 2a instrução
            $linha .= '00';

            // 161 a 173 Valor a ser cobrado por Dia de Atraso
            $juros = round((float)$titulo->saldo * (0.04 / 30), 2);
            $linha .= str_pad($juros * 100, 13, '0', STR_PAD_LEFT);

            // 174 a 179 Data Limite P/Concessão de Desconto
            $linha .= str_pad(0, 6, '0', STR_PAD_LEFT);

            // 180 a 192 Valor do Desconto
            $linha .= str_pad(0, 13, '0', STR_PAD_LEFT);

            // 193 a 205 Valor do IOF
            $linha .= str_pad(0, 13, '0', STR_PAD_LEFT);

            // 206 a 218 Valor do Abatimento a ser concedido ou cancelado
            $linha .= str_pad(0, 13, '0', STR_PAD_LEFT);

            // 219 a 220 Identificação do Tipo de Inscrição do Pagador
            $linha .= $titulo->Pessoa->fisica ? '01' : '02';

            // 221 a 234 No Inscrição do Pagador
            $linha .= str_pad($titulo->Pessoa->cnpj, 14, '0', STR_PAD_LEFT);

            // 235 a 274 Nome do Pagador
            $linha .= substr(str_pad(strtoupper($titulo->Pessoa->pessoa), 40, ' ', STR_PAD_RIGHT), 0, 40);

            // 275 a 314 Endereço Completo
            $end = $titulo->Pessoa->enderecocobranca;
            $end .= ' ';
            $end .= $titulo->Pessoa->numerocobranca;
            $end .= ' ';
            $end .= $titulo->Pessoa->complementocobranca;
            $end .= ' ';
            $end .= $titulo->Pessoa->bairrocobranca;
            $end .= ' ';
            $end .= $titulo->Pessoa->CidadeCobranca->cidade;
            $end .= '/';
            $end .= $titulo->Pessoa->CidadeCobranca->Estado->sigla;
            $end = str_replace('  ', ' ', $end);
            $linha .= substr(str_pad(strtoupper($end), 40, ' ', STR_PAD_RIGHT), 0, 40);

            // 315 a 326 1a Mensagem
            $linha .= str_pad('', 12, ' ', STR_PAD_RIGHT);

            // 327 a 331 CEP
            // 332 a 334 Sufixo do CEP
            $linha .= str_pad($titulo->Pessoa->cepcobranca, 8, '0', STR_PAD_LEFT);

            // 335 a 394 Sacador/Avalista ou 2a Mensagem
            $linha .= str_pad('', 60, ' ', STR_PAD_RIGHT);

            // 395 a 400 No Seqüencial do Registro
            $linha .= str_pad($iLinha, 6, '0', STR_PAD_LEFT);
            $linhas[] = $linha;
        }

        // Registro 9 - Trailler - Rodape
        $iLinha++;
        $linha = '9';
        $linha .= str_pad('', 393, ' ', STR_PAD_RIGHT);
        $linha .= str_pad($iLinha, 6, '0', STR_PAD_LEFT);
        $linhas[] = $linha;

        // Linha em branco pro bradesco ficar feliz
        $linhas[] = '';

        // salva arquivo
        $linhas = implode("\r\n", $linhas);
        Storage::disk('boleto')->put($arquivo, $linhas);

        // retorna nome do arquivo gerado
        $info = pathinfo($arquivo);
        return $info['basename'];
    }

    public static function calculaDVNossoNumeroBradesco($nossonumero)
    {
        // declara as variáveis
        $intContador  = 0;
        $intNumero  = 0;
        $intTotalNumero = 0;
        $intMultiplicador  = 2;
        $intResto = 0;

        // pega cada caracter do numero a partir da direita
        for ($intContador = strlen($nossonumero); $intContador >= 1; $intContador--) {
            // extrai o caracter e multiplica prlo multiplicador
            $intNumero = substr($nossonumero, $intContador - 1, 1) * $intMultiplicador;
            // soma o resultado para totalização
            $intTotalNumero += $intNumero;
            // se o multiplicador for maior que 2 decrementa-o caso contrario atribuir valor padrao original
            $intMultiplicador = ($intMultiplicador < 7) ? $intMultiplicador + 1 : 2;
        }

        // calcula o resto da divisao do total por 11
        $intResto = $intTotalNumero % 11;

        switch ($intResto) {
            case 1:
                $digito = 'P';
                break;

            case 0:
                $digito = '0';
                break;

            default:
                $digito = 11 - $intResto;
                break;
        }
        return $digito;
    }
}
