<?php

namespace Mg\Titulo\BoletoBb;

use DB;
use Carbon\Carbon;

use Dompdf\Dompdf;

use OpenBoleto\Banco\BancoDoBrasil;
use OpenBoleto\Agente;

use JasperPHP\Instructions;
use JasperPHP\Report;
use JasperPHP\PdfProcessor;

use Mg\Titulo\Titulo;
use Mg\Titulo\TituloBoleto;
use Mg\Portador\Portador;

class BoletoBbService
{

    /***
     * Verifica se o Token do Portador ainda não expirou
     * se expirou renova o token
     */
    public static function verificaTokenValido (Portador $portador)
    {
        if (!empty($portador->bbtokenexpiracao)) {
            if ($portador->bbtokenexpiracao->isFuture()) {
                return $portador->bbtoken;
            }
        }
        $token = BoletoBbApiService::token();
        $expiracao = Carbon::now()->addSeconds($token['expires_in']);
        $portador->update([
            'bbtoken' => $token['access_token'],
            'bbtokenexpiracao' => $expiracao,
        ]);
        return $portador->bbtoken;
    }

    /**
     * Monta Nosso Numero de Acordo com Documentacao do BB:
     * Número de identificação do boleto (correspondente ao NOSSO NÚMERO),
     * no formato STRING, com 20 dígitos, que deverá ser formatado da
     * seguinte forma:  “000” +  (número do convênio com 7 dígitos) + (10
     * algarismos - se necessário, completar com zeros à esquerda).
     */
    public static function atribuirNossoNumero (Titulo $titulo)
    {
        // Caso ja tenha numero atribuido, aborta
        if (!empty($titulo->nossonumero)) {
            return $titulo->nossonumero;
        }

        // Monta nome da Sequence
        $sequence = "tbltitulo_nossonumero_{$titulo->codportador}_seq";

        // Cria Sequence se nao existir
        $sql = "CREATE SEQUENCE IF NOT EXISTS {$sequence}";
        DB::statement($sql);

        // Busca proximo numero da sequence
        $sql = 'SELECT NEXTVAL(:sequence) AS numero';
        $res = DB::select($sql, ['sequence' => $sequence]);
        $nossonumero = '000';
        $nossonumero .= str_pad((int)$titulo->Portador->convenio, 7,  '0', STR_PAD_LEFT);
        $nossonumero .= str_pad((int)$res[0]->numero, 10,  '0', STR_PAD_LEFT);
        $titulo->update([
            'nossonumero' => $nossonumero
        ]);
        return $nossonumero;
    }


    /**
     * Registra Boleto na API do BB
     */
    public static function registrar(Titulo $titulo)
    {
        $bbtoken = static::verificaTokenValido($titulo->Portador);
        $endereco = $titulo->Pessoa->enderecocobranca;
        if (!empty($titulo->Pessoa->numerocobranca)) {
            $endereco .= ", {$titulo->Pessoa->numerocobranca}";
        }
        if (!empty($titulo->Pessoa->complementocobranca)) {
            $endereco .= " - {$titulo->Pessoa->complementocobranca}";
        }
        $numeroTituloBeneficiario = preg_replace("/\//", '-', $titulo->numero);
        $nossonumero = static::atribuirNossoNumero($titulo);
        $ret = BoletoBbApiService::registrar(
            $bbtoken,
            (int)$titulo->Portador->convenio,
            $titulo->Portador->carteira,
            $titulo->Portador->carteiravariacao,
            $titulo->emissao,
            $titulo->vencimento,
            $titulo->saldo,
            $numeroTituloBeneficiario,
            $nossonumero,
            $titulo->Pessoa->fisica?1:2,
            (int)$titulo->Pessoa->cnpj,
            $titulo->Pessoa->pessoa,
            $endereco,
            $titulo->Pessoa->cepcobranca,
            $titulo->Pessoa->CidadeCobranca->cidade,
            $titulo->Pessoa->bairrocobranca,
            $titulo->Pessoa->CidadeCobranca->Estado->sigla,
            $titulo->Pessoa->telefone1??$titulo->Pessoa->telefone2
        );
        if (isset($ret['erros'])) {
            throw new \Exception("{$ret['erros'][0]['mensagem']} - {$ret['erros'][0]['codigo']}", 0);
        }
        $tituloBoleto = TituloBoleto::firstOrNew([
            'nossonumero' => $nossonumero,
            'codportador' => $titulo->codportador
        ]);
        $tituloBoleto->codtitulo = $titulo->codtitulo;
        $tituloBoleto->linhadigitavel = $ret['linhaDigitavel'];
        $tituloBoleto->barras = $ret['codigoBarraNumerico'];
        $tituloBoleto->qrcodeurl = $ret['qrCode']['url'];
        $tituloBoleto->qrcodetxid = $ret['qrCode']['txId'];
        $tituloBoleto->qrcodeemv = $ret['qrCode']['emv'];
        $tituloBoleto->save();
        return $tituloBoleto;
    }

    /**
     * Consulta Boleto na API do BB e persiste retorno na tbltituloboleto
     */
    public static function consultar(TituloBoleto $tituloBoleto)
    {
        $bbtoken = static::verificaTokenValido($tituloBoleto->Portador);
        $ret = BoletoBbApiService::consultar(
            $bbtoken,
            $tituloBoleto->Portador->convenio,
            $tituloBoleto->nossonumero
        );

        $tituloBoleto->update([
            'linhadigitavel' => $ret['codigoLinhaDigitavel'],
            'canalpagamento' => $ret['codigoCanalPagamento'],
            'estadotitulocobranca' => $ret['codigoEstadoTituloCobranca'],
            'dataregistro' => (!empty($ret['dataRegistroTituloCobranca']))?Carbon::parse($ret['dataRegistroTituloCobranca']):null,
            'vencimento' => (!empty($ret['dataVencimentoTituloCobranca']))?Carbon::parse($ret['dataVencimentoTituloCobranca']):null,
            'valororiginal' => $ret['valorOriginalTituloCobranca'],
            'valoratual' => $ret['valorAtualTituloCobranca'],
            'valorpagamentoparcial' => $ret['valorPagamentoParcialTitulo'],
            'valorabatimento' => $ret['valorAbatimentoTituloCobranca'],
            'databaixaautomatica' => (!empty($ret['dataBaixaAutomaticoTitulo']))?Carbon::parse($ret['dataBaixaAutomaticoTitulo']):null,
            'valorjuromora' => $ret['valorJuroMoraRecebido'],
            'valordesconto' => $ret['valorDescontoUtilizado'],
            'valorpago' => $ret['valorPagoSacado'],
            'valorliquido' => $ret['valorCreditoCedente'],
            'datarecebimento' => (!empty($ret['dataRecebimentoTitulo']))?Carbon::parse($ret['dataRecebimentoTitulo']):null,
            'datacredito' => (!empty($ret['dataCreditoLiquidacao']))?Carbon::parse($ret['dataCreditoLiquidacao']):null,
            'tipobaixatitulo' => $ret['codigoTipoBaixaTitulo'],
            'valormulta' => $ret['valorMultaRecebido'],
            'valorreajuste' => $ret['valorReajuste'],
            'valoroutro' => $ret['valorOutroRecebido'],
        ]);

        return $tituloBoleto;
    }

    public static function pdf (TituloBoleto $tituloBoleto)
    {
        $report = new Report(app_path('/Mg/Titulo/BoletoBb/boletoA4.jrxml'), []);
        Instructions::prepare($report); // prepara o relatorio lendo o arquivo
        $data = [
            new BoletoBbPdf($tituloBoleto),
        ];
        $report->dbData = $data; // aqui voce pode construir seu array de boletos em qualquer estrutura incluindo
        $report->generate();                // gera o relatorio
        $report->out();                     // gera o pdf
        $pdfProcessor = PdfProcessor::get();       // extrai o objeto pdf de dentro do report
        $pdf = $pdfProcessor->Output('boleto.pdf', 'S');  // metodo do TCPF para gerar saida para o browser
        return $pdf;
    }
}
