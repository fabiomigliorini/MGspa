<?php

namespace Mg\Boleto;

use Mg\Pagamento\PagamentoService;
use Mg\Pagamento\PagamentoTituloService;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Mg\Portador\Portador;
use Mg\Titulo\Titulo;
use Mg\Titulo\MovimentoTituloService;

class BoletoRetornoService
{
    public static function processarRetorno($codportador, $arquivo)
    {
        // Instancia Portador
        $portador = Portador::findOrFail($codportador);

        // Carrega arquivo de retorno
        $origem = "{$portador->conta}-{$portador->contadigito}/Retorno/{$arquivo}";
        $linhas = Storage::disk('boleto')->get($origem);
        $linhas = explode("\n", $linhas);

        $sucesso = 0;
        $falha = 0;
        $numeroLinhas = 0;

        // percorre linhas
        foreach ($linhas as $linha) {
            if (empty($linha)) {
                continue;
            }

            $numeroLinhas++;

            // pelo tipo de registro decide o que fazer
            $tipo = substr($linha, 0, 1);
            switch ($tipo) {
                case '0': // Header
                    $convenio = substr($linha, 26, 20);
                    if ($portador->convenio != $convenio) {
                        throw new \Exception("Convênio do Arquivo diferente do cadastrado para o Portador!");
                    }
                    $codbanco = substr($linha, 76, 3);
                    if ($portador->codbanco != $codbanco) {
                        throw new \Exception("Banco do Arquivo diferente do cadastrado para o Portador!");
                    }
                    $dataretorno = substr($linha, 379, 6);
                    $dataretorno = Carbon::createFromFormat('dmy', $dataretorno);
                    break;

                case '1': // Transacao
                case '3': // Rateio de Credito
                    if (static::importarBoletoRetorno($arquivo, $linha, $dataretorno, $portador)) {
                        $sucesso++;
                    } else {
                        $falha++;
                    }
                    break;

                case '9': // Trailer
                    break;

                default:
                    throw new \Exception("Não sei o que fazer com registro de retorno tipo '$tipo'!");
                    break;
            }
        }

        // move arquivo para pasta de "processados"
        static::arquivarRetorno($portador, $arquivo, $dataretorno);
        return [
            'codportador' => $codportador,
            'arquivo' => $arquivo,
            'linhas' => $numeroLinhas,
            'registros' => $sucesso + $falha,
            'sucesso' => $sucesso,
            'falha' => $falha
        ];
    }

    public static function arquivarRetorno($portador, $arquivo, $dataretorno)
    {
        $info = pathinfo($arquivo);
        $origem = "{$portador->conta}-{$portador->contadigito}/Retorno/{$info['basename']}";
        $mesano = $dataretorno->format('Y/m');
        $destino = "{$portador->conta}-{$portador->contadigito}/Retorno/Processado/{$mesano}/{$info['basename']}";
        if (Storage::disk('boleto')->exists($destino)) {
            Storage::disk('boleto')->delete($destino);
        }
        return Storage::disk('boleto')->move($origem, $destino);
    }

    public static function importarBoletoRetorno($arquivo, $linha, Carbon $dataretorno, Portador $portador)
    {
        // abre o registro ou cria um novo
        $numeroLinha = substr($linha, 394, 6);
        $br = BoletoRetorno::firstOrNew([
            'codportador' => $portador->codportador,
            'dataretorno' => $dataretorno,
            'arquivo' => $arquivo,
            'linha' => $numeroLinha,
        ]);

        // importa dados que o banco enviou
        $br->numero = trim(substr($linha, 37, 25));
        $br->nossonumero = trim(substr($linha, 70, 12));
        $br->codboletomotivoocorrencia = substr($linha, 0, 1) .
            substr($linha, 108, 2) . '0' . substr($linha, 318, 2);
        $br->valor = substr($linha, 152, 13) / 100;
        $br->codbancocobrador = substr($linha, 165, 3);
        $br->agenciacobradora = trim(substr($linha, 168, 5));
        $br->despesas = substr($linha, 175, 13) / 100;
        $br->outrasdespesas = substr($linha, 188, 13) / 100;
        $br->jurosatraso = substr($linha, 201, 13) / 100;
        $br->abatimento = substr($linha, 227, 13) / 100;
        $br->desconto = substr($linha, 240, 13) / 100;
        $br->pagamento = substr($linha, 253, 13) / 100;
        $br->jurosmora = substr($linha, 266, 13) / 100;

        // se valores vem zerados, seta nulo
        $br->valor = ($br->valor>0)?$br->valor:null;
        $br->despesas = ($br->despesas>0)?$br->despesas:null;
        $br->outrasdespesas = ($br->outrasdespesas>0)?$br->outrasdespesas:null;
        $br->jurosatraso = ($br->jurosatraso>0)?$br->jurosatraso:null;
        $br->abatimento = ($br->abatimento>0)?$br->abatimento:null;
        $br->desconto = ($br->desconto>0)?$br->desconto:null;
        $br->pagamento = ($br->pagamento>0)?$br->pagamento:null;
        $br->jurosmora = ($br->jurosmora>0)?$br->jurosmora:null;

        $br->protesto = trim(substr($linha, 294, 1));

        // salva arquivo
        if (!$br->save()) {
            throw new \Exception("Erro gravando registro de 'BoletoRetorno'!");
        }
        return static::processarBoletoRetorno($br);
    }

    public static function processarBoletoRetorno (BoletoRetorno $br)
    {
        // tenta vincular com titulo
        $titulo = Titulo::where([
            'nossonumero' => substr($br->nossonumero, 0, 11),
            'codportador' => $br->codportador
        ])->first();
        if (!$titulo) {
            return false;
        }
        $br->codtitulo = $titulo->codtitulo;
        if (!$br->save()) {
            return false;
        }

        //conforme o tipo do movimento decide o que fazer
        $tipo = substr($br->codboletomotivoocorrencia, 0, 3);
        switch ($tipo) {

            // Liquidacao
            case '106':
            case '115':
            case '117':

                $vinculos = [
                    'codboletoretorno' => $br->codboletoretorno,
                    'codportador' => $br->codportador,
                    'transacao' => $br->dataretorno,
                ];

                // Uma linha: juros e desconto junto com o pago. O que sai do
                // saldo e' o pago sem os juros e com o desconto.
                $juros = (float) $br->jurosatraso + (float) $br->jurosmora;
                $desconto = (float) $br->abatimento + (float) $br->desconto;
                $pagamento = (float) $br->pagamento;
                $principal = $pagamento - $juros + $desconto;
                if ($pagamento > 0 || $principal != 0) {
                    $mov = MovimentoTituloService::lancar(
                        $titulo,
                        MovimentoTituloService::TIPO_LIQUIDACAO,
                        -$principal,
                        ['juros' => $juros, 'desconto' => $desconto, 'total' => -$pagamento],
                        $vinculos,
                        ['codboletoretorno']
                    );
                    PagamentoTituloService::daBaixa($mov, PagamentoService::MEIO_BOLETO, $br->codportador, $br->dataretorno);
                }
                break;

            // Estorno de Pagamento (ocorrência 40)
            case '140':
                $juros = (float) $br->jurosatraso + (float) $br->jurosmora;
                $desconto = (float) $br->abatimento + (float) $br->desconto;
                $pagamento = (float) $br->pagamento;
                $principal = $pagamento - $juros + $desconto;
                if ($pagamento > 0 || $principal != 0) {
                    $mov = MovimentoTituloService::lancar(
                        $titulo,
                        MovimentoTituloService::TIPO_ESTORNO_LIQUIDACAO,
                        $principal,
                        ['juros' => $juros, 'desconto' => $desconto, 'total' => $pagamento],
                        [
                            'codboletoretorno' => $br->codboletoretorno,
                            'codportador' => $br->codportador,
                            'transacao' => $br->dataretorno,
                        ],
                        ['codboletoretorno']
                    );
                    // saiu do banco: pagamento no sentido contrario
                    PagamentoTituloService::daBaixa($mov, PagamentoService::MEIO_BOLETO, $br->codportador, $br->dataretorno);
                }
                break;

            default:
                // code...
                break;
        }

        return true;
    }

    public static function reprocessarRetorno ()
    {
        $sucesso = 0;
        $falha = 0;
        $brs = BoletoRetorno::whereNull('codtitulo')->get();
        foreach ($brs as $br){
            if (static::processarBoletoRetorno($br)) {
                $sucesso++;
            } else {
                $falha++;
            }
        }
        return [
            'registros' => $sucesso + $falha,
            'sucesso' => $sucesso,
            'falha' => $falha
        ];
    }
}
