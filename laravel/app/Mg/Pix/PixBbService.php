<?php

namespace Mg\Pix;

use Illuminate\Support\Facades\Log;

use Mg\Titulo\BoletoBb\BoletoBbService;

// use DB;
// use Carbon\Carbon;
//
// use Dompdf\Dompdf;
//
// use OpenBoleto\Banco\BancoDoBrasil;
// use OpenBoleto\Agente;
//
// use JasperPHP\Instructions;
// use JasperPHP\Report;
// use JasperPHP\PdfProcessor;
//
// use Mg\Titulo\Titulo;
// use Mg\Titulo\TituloBoleto;
// use Mg\Titulo\MovimentoTitulo;
// use Mg\Titulo\TipoMovimentoTitulo;
// use Mg\Portador\Portador;
// use Mg\Negocio\Negocio;

class PixBbService
{

    public static function transmitirPixCob (PixCob $pixCob)
    {
        $bbtoken = BoletoBbService::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixBbApiService::transmitirPixCob(
            $bbtoken,
            $pixCob->Portador->bbdevappkey,
            $pixCob->Portador->pixdict,
            $pixCob->txid,
            $pixCob->expiracao,
            $pixCob->valororiginal,
            $pixCob->solicitacaopagador,
            $pixCob->nome,
            $pixCob->cpf,
            $pixCob->cnpj
        );
        if (!empty($dadosPix['erros'])) {
            throw new \Exception('API do BB retornou: ' . $dadosPix['erros'][0]['mensagem']);
        }
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);
        $ret = $pixCob->update([
            'location' => $dadosPix['location'],
            'textoimagemqrcode' => $dadosPix['textoImagemQRcode'],
            'txid' => $dadosPix['txid'],
            // 'locationid' => $dadosPix['loc']['id']??null,
            'codpixcobstatus' => $status->codpixcobstatus
        ]);
        return $pixCob;
    }

    public static function qrCode($textoImagemQRcode)
    {

        $url = 'https://chart.googleapis.com/chart?chs=513x513&cht=qr&chl=' .
            urlencode($textoImagemQRcode);
        $curl = curl_init();
        $opt = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
        ];
        curl_setopt_array($curl, $opt);
        $img = curl_exec($curl);
        return $img;
    }

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
        $token = BoletoBbApiService::token($portador);
        $expiracao = Carbon::now()->addSeconds($token['expires_in'] * 0.5);
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
        // if (!empty($titulo->nossonumero)) {
        //     return $titulo->nossonumero;
        // }

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
            'boleto' => true,
            'nossonumero' => $nossonumero
        ]);
        return $nossonumero;
    }


    /**
     * Registra Boleto na API do BB
     */
    public static function registrar(Titulo $titulo)
    {

        // se tem portador de outro banco
        if (empty($titulo->codportador) || $titulo->Portador->codbanco != 1) {

            // se ja tem boleto registrado em outro banco
            if ($titulo->boleto && !empty($titulo->nossonumero)) {
                throw new \Exception('Já existe boleto registrado em outro banco!');
            }

            //procura portador do BB pra filial com convenio
            $portador = Portador::where('codfilial', $titulo->codfilial)
                ->whereNull('inativo')
                ->where('codbanco', 1)
                ->whereNotNull('convenio')
                ->orderBy('codportador')
                ->first();

            //procura portador do BB sem filial com convenio
            if ($portador === null) {
                $portador = Portador::whereNull('codfilial')
                    ->whereNull('inativo')
                    ->where('codbanco', 1)
                    ->whereNotNull('convenio')
                    ->orderBy('codportador')
                    ->first();
            }

            // se nao localizou nenhum portador
            if ($portador === null) {
                throw new \Exception('Nenhum portador disponível para a filial');
            }

            // associa o portador ao titulo
            $titulo->update([
                'codportador' => $portador->codportador,
                'nossonumero' => null
            ]);

            $titulo = $titulo->fresh();
        }

        // verifica se tem token valido
        $bbtoken = static::verificaTokenValido($titulo->Portador);

        // monta variaveis com dados da cobranca
        $endereco = $titulo->Pessoa->enderecocobranca;
        if (!empty($titulo->Pessoa->numerocobranca)) {
            $endereco .= ", {$titulo->Pessoa->numerocobranca}";
        }
        if (!empty($titulo->Pessoa->complementocobranca)) {
            $endereco .= " - {$titulo->Pessoa->complementocobranca}";
        }
        $numeroTituloBeneficiario = strtoupper(preg_replace('(/|\s)', '-', $titulo->numero));

        // monta "nossonumero"
        $nossonumero = static::atribuirNossoNumero($titulo);

        $telefone = ($titulo->Pessoa->telefone1??$titulo->Pessoa->telefone2)??$titulo->Pessoa->telefone3;
        $telefone = preg_replace('/\D/', '', $telefone);

        // registra o boleto no BB
        $ret = BoletoBbApiService::registrar(
            $bbtoken,
            $titulo->Portador->bbdevappkey,
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
            $telefone
        );

        // verifica se houve erro
        if (isset($ret['erros'])) {
            throw new \Exception("{$ret['erros'][0]['mensagem']} - {$ret['erros'][0]['codigo']}", 0);
        }

        // armazena dados do registro
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
        $tituloBoleto->estadotitulocobranca = $tituloBoleto->estadotitulocobranca??1; //Normal
        $tituloBoleto->vencimento = $tituloBoleto->vencimento??$titulo->vencimento;
        $tituloBoleto->dataregistro = $tituloBoleto->dataregistro??Carbon::now();
        $tituloBoleto->databaixaautomatica = $tituloBoleto->databaixaautomatica??$titulo->vencimento->addDays(95);
        $tituloBoleto->valororiginal = $tituloBoleto->valororiginal??$titulo->saldo;
        $tituloBoleto->valoratual = $tituloBoleto->valoratual??$titulo->saldo;
        $tituloBoleto->save();
        return $tituloBoleto;
    }

    /**
     * Registra boleto de todos os titulos do Negocio
     */
    public static function registrarPeloNegocio (Negocio $negocio)
    {
        $tituloBoletos = collect();
        foreach ($negocio->NegocioFormaPagamentoS()->orderBy('codnegocioformapagamento')->get() as $nfp) {
            if (!$nfp->FormaPagamento->boleto) {
                continue;
            }
            foreach ($nfp->TituloS()->where('saldo', '>', 0)->orderBy('vencimento', 'ASC')->get() as $titulo) {
                $tituloBoletos[] = static::registrar($titulo);
            }
        }
        return $tituloBoletos;
    }


    /**
     * Consulta Boleto na API do BB e persiste retorno na tbltituloboleto
     */
    public static function consultar(TituloBoleto $tituloBoleto)
    {
        $bbtoken = static::verificaTokenValido($tituloBoleto->Portador);
        $ret = BoletoBbApiService::consultar(
            $bbtoken,
            $tituloBoleto->Portador->bbdevappkey,
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

        $tituloBoleto = static::liquidar($tituloBoleto);

        if ($tituloBoleto == false) {
            throw new \Exception('Falha ao processar liquidação do Boleto');
        }

        return $tituloBoleto;
    }

    /**
     * Solicita Baixa do Boleto na API do BB e persiste retorno no campo tbltituloboleto.inativo
     */
    public static function baixar(TituloBoleto $tituloBoleto)
    {
        $bbtoken = static::verificaTokenValido($tituloBoleto->Portador);
        $ret = BoletoBbApiService::baixar(
            $bbtoken,
            $tituloBoleto->Portador->bbdevappkey,
            $tituloBoleto->Portador->convenio,
            $tituloBoleto->nossonumero
        );

        if (isset($ret['errors'][0])) {
            throw new \Exception($ret['errors'][0]['message'], $ret['errors'][0]['code']);
        }
        $inativo = Carbon::parse($ret['dataBaixa'] . ' ' . $ret['horarioBaixa'] . ' America/Sao_Paulo')->timezone(config('app.timezone'));
        $tituloBoleto->update([
            'inativo' => $inativo,
            'estadotitulocobranca' => 7, // Baixado
        ]);
        return $tituloBoleto;
    }

    /**
     * Gera o arquivo PDF do Boleto
     */
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

    /**
     * Gera o arquivo PDF dos Boleto do Negocio
     */
    public static function pdfPeloNegocio (Negocio $negocio)
    {
        $report = new Report(app_path('/Mg/Titulo/BoletoBb/boletoA4.jrxml'), []);
        Instructions::prepare($report); // prepara o relatorio lendo o arquivo
        $data = [];
        foreach ($negocio->NegocioFormaPagamentoS()->orderBy('codnegocioformapagamento')->get() as $nfp) {
            foreach ($nfp->TituloS()->where('saldo', '>', 0)->orderBy('vencimento', 'ASC')->get() as $titulo) {
                foreach ($titulo->TituloBoletoS()->whereNull('inativo')->orderBy('codtituloboleto')->get() as $tituloBoleto) {
                    $data[] = new BoletoBbPdf($tituloBoleto);
                }
            }
        }
        if (count($data) == 0) {
            throw new \Exception("Nenhum boleto para o Negócio!");
        }
        $report->dbData = $data; // aqui voce pode construir seu array de boletos em qualquer estrutura incluindo
        $report->generate();                // gera o relatorio
        $report->out();                     // gera o pdf
        $pdfProcessor = PdfProcessor::get();       // extrai o objeto pdf de dentro do report
        $pdf = $pdfProcessor->Output('boleto.pdf', 'S');  // metodo do TCPF para gerar saida para o browser
        return $pdf;
    }

    /**
     * Gera o registro da liquidacao do Boleto caso estado 6 - Liquidado
     */
    public static function liquidar (TituloBoleto $tituloBoleto)
    {

        /*
        // TODO: Decidir o que fazer com esses 4 campos
        if ($tituloBoleto->valorpagamentoparcial > 0) { }
        if ($tituloBoleto->valorabatimento > 0) { }
        if ($tituloBoleto->valorreajuste > 0) { }
        if ($tituloBoleto->valoroutro > 0) { }
        */

        // acumula cod dos movimentos gerados
        $codmovimentotitulos = [];

        // lanca juros
        if ($tituloBoleto->valorjuromora > 0) {
            $mov = MovimentoTitulo::firstOrNew([
                'codtituloboleto' => $tituloBoleto->codtituloboleto,
                'codtipomovimentotitulo' => TipoMovimentoTitulo::JUROS,
            ]);
            $mov->codtitulo = $tituloBoleto->codtitulo;
            $mov->codportador = $tituloBoleto->codportador;
            $mov->transacao = $tituloBoleto->datarecebimento;
            $mov->debito = $tituloBoleto->valorjuromora;
            if (!$mov->save()) {
                return false;
            }
            $codmovimentotitulos[] = $mov->codmovimentotitulo;
        }

        // lanca Multa
        if ($tituloBoleto->valormulta > 0) {
            $mov = MovimentoTitulo::firstOrNew([
                'codtituloboleto' => $tituloBoleto->codtituloboleto,
                'codtipomovimentotitulo' => TipoMovimentoTitulo::MULTA,
            ]);
            $mov->codtitulo = $tituloBoleto->codtitulo;
            $mov->codportador = $tituloBoleto->codportador;
            $mov->transacao = $tituloBoleto->datarecebimento;
            $mov->debito = $tituloBoleto->valormulta;
            if (!$mov->save()) {
                return false;
            }
            $codmovimentotitulos[] = $mov->codmovimentotitulo;
        }

        // lanca desconto
        if ($tituloBoleto->valordesconto > 0) {
            $mov = MovimentoTitulo::firstOrNew([
                'codtituloboleto' => $tituloBoleto->codtituloboleto,
                'codtipomovimentotitulo' => TipoMovimentoTitulo::DESCONTO,
            ]);
            $mov->codtitulo = $tituloBoleto->codtitulo;
            $mov->codportador = $tituloBoleto->codportador;
            $mov->transacao = $tituloBoleto->datarecebimento;
            $mov->credito = $tituloBoleto->valordesconto;
            if (!$mov->save()) {
                return false;
            }
            $codmovimentotitulos[] = $mov->codmovimentotitulo;
        }

        // lanca valor do pagamento
        if ($tituloBoleto->valorpago > 0) {
            $mov = MovimentoTitulo::firstOrNew([
                'codtituloboleto' => $tituloBoleto->codtituloboleto,
                'codtipomovimentotitulo' => TipoMovimentoTitulo::LIQUIDACAO,
            ]);
            $mov->codtitulo = $tituloBoleto->codtitulo;
            $mov->codportador = $tituloBoleto->codportador;
            $mov->transacao = $tituloBoleto->datarecebimento;
            $mov->credito = $tituloBoleto->valorpago;
            if (!$mov->save()) {
                return false;
            }
            $codmovimentotitulos[] = $mov->codmovimentotitulo;
        }

        // apaga movimentos que sobraram
        MovimentoTitulo::
            where('codtituloboleto', $tituloBoleto->codtituloboleto)
            ->whereNotIn('codmovimentotitulo', $codmovimentotitulos)
            ->delete();

        // retorna o mesmo objeto que recebeu
        return $tituloBoleto;

    }

    /**
     * Consulta listagem de titulos liquidados e processa os novos
     */
    public static function consultarLiquidados ()
    {

        // busca todos os portadores com bbdevappkey
        $portadores = Portador::
            whereNull('inativo')
            ->whereNotNull('bbdevappkey')
            ->orderBy('codportador')
            ->get();

        // consulta de 15 dias atras ate hoje
        $dataFimMovimento = Carbon::now();
        $dataInicioMovimento = Carbon::now()->subDays(15);

        // percorre portadores
        foreach ($portadores as $portador) {

            // inicializa indice de consulta
            $indice = 0;

            // percorre enquanto indicador de continuidade = 'S'
            do {

                // loga pesquisa
                Log::info("Boleto BB - Consultando Liquidados - Portador #{$portador->codportador} - Indice {$indice}");

                // autentica na API
                $bbtoken = static::verificaTokenValido($portador);

                // pega listagem dos boletos baixados / pagos
                $listagem = BoletoBbApiService::consultarListagem (
                    $bbtoken,
                    $portador->bbdevappkey,
                    'B',
                    $portador->agencia,
                    $portador->conta,
                    $dataInicioMovimento,
                    $dataFimMovimento,
                    $indice
                );

                // se listagem vazia  cai fora
                if ($listagem == null) {
                    break;
                }

                // precorre lsitagem de boletos
                foreach ($listagem['boletos'] as $bol) {

                    // procura registro pelo nosso numero
                    $tituloBoleto = TituloBoleto::where([
                        'nossonumero' => $bol['numeroBoletoBB'],
                        'codportador' => $portador->codportador
                    ])->first();

                    // se nao encontrou ignora
                    if (!$tituloBoleto) {
                        continue;
                    }

                    // se tem valor pago != do registrado no banco
                    // ou se o estado do boleto e diferente
                    if ($tituloBoleto->valorpago != $bol['valorPago'] ||
                        $tituloBoleto->estadotitulocobranca != $bol['codigoEstadoTituloCobranca']) {
                        // consulta o boleto
                        Log::info("Boleto BB - Consultando TituloBoleto #{$tituloBoleto->codtituloboleto}");
                        $tituloBoleto = static::consultar($tituloBoleto);
                    } else {
                        // persiste os dados que api retornou
                        $dataCredito = $bol['dataCredito'];
                        if ($dataCredito == '01.01.0001') {
                            $dataCredito = null;
                        } else {
                            $dataCredito = Carbon::parse($dataCredito);
                        }
                        $tituloBoleto = $tituloBoleto->update([
                            'dataregistro' => Carbon::parse($bol['dataRegistro']),
                            'vencimento' => Carbon::parse($bol['dataVencimento']),
                            'valororiginal' => $bol['valorOriginal'],
                            'valoratual' => $bol['valorAtual'],
                            'datacredito' => $dataCredito,
                        ]);
                    }
                }

                // pega o indice para continuar consulta
                $indice = $listagem['proximoIndice'];

            // repete enquanto api retornar indicadorContinuidade
            } while ($listagem['indicadorContinuidade'] == 'S');
        }
    }

}
