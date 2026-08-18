<?php

namespace Mg\NFePHP;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Mg\MgService;
use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalCartaCorrecao;
use Mg\Filial\Filial;
use Mg\Filial\Empresa;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\NotaFiscalStatusService;
use NFePHP\Common\Exception\SoapException;
use NFePHP\NFe\Complements;
use NFePHP\NFe\Common\Standardize;
use NFePHP\Common\Strings;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\NFe\Danfce;

class NFePHPService extends MgService
{
    const EXCEPTION_CHAVE_NFE_AUSENTE = 999;
    const EXCEPTION_XML_ASSINADO_INEXISTENTE = 998;

    /**
     * TTL do lock.
     *
     * Era 120s, MENOR que o pior caso real de enviarSincrono (~243s: envio com 3
     * tentativas de 40s + consulta de recuperação com outras 3). Ou seja, o lock expirava
     * com o processo ainda vivo e uma segunda operação entrava em paralelo na mesma nota
     * — exatamente o que ele deveria impedir. 300s cobre a operação mais longa com folga
     * e continua curto o bastante para não travar a nota se o processo morrer.
     */
    const LOCK_TTL = 300;

    /**
     * Serializa operações concorrentes sobre o mesmo alvo.
     *
     * Tenta adquirir um Cache::lock atômico. Se já houver outro processo segurando o
     * lock, lança \Exception imediatamente.
     *
     * O objeto retornado é um "guard" com __destruct() que libera o lock
     * automaticamente quando a variável cair fora de escopo (no return ou em qualquer
     * exception do corpo do método chamador) — sem try/finally.
     *
     * O TTL serve apenas como fallback caso o processo morra sem chegar ao destructor
     * (segfault, kill -9, etc).
     *
     * Além do lock, grava um MARCADOR na store de cache normal. Isso é necessário porque
     * Cache::has() NÃO enxerga a chave do lock: config/cache.php define
     * lock_connection = 'default' (Redis DB 0) e connection = 'cache' (DB 1) — mesmo
     * prefixo, database diferente. O marcador é o que permite ao robô perguntar
     * "esta nota está ocupada?" sem tentar adquirir o lock (o que o roubaria).
     */
    protected static function lock(string $chave, ?string $mensagem = null)
    {
        $lock = Cache::lock("nfephp-{$chave}", static::LOCK_TTL);

        if (!$lock->get()) {
            throw new \Exception($mensagem ?? "Outra operação já está em andamento. Tente novamente.");
        }

        $marcador = "nfephp-ocupado-{$chave}";
        Cache::put($marcador, now()->toIso8601String(), static::LOCK_TTL);

        return new class($lock, $marcador) {
            public function __construct(private $lock, private string $marcador) {}
            public function __destruct()
            {
                try {
                    $this->lock->release();
                    Cache::forget($this->marcador);
                } catch (\Throwable $e) {
                    // ignora — TTL do lock e do marcador servem como fallback
                }
            }
        };
    }

    protected static function lockDaNotaFiscal(NotaFiscal $nf)
    {
        return static::lock(
            "nf-{$nf->codnotafiscal}",
            "Outra operação já está em andamento para a Nota Fiscal #{$nf->codnotafiscal}. Tente novamente."
        );
    }

    /**
     * Há operação em andamento nesta nota?
     *
     * Leitura pura: não adquire o lock (o que o roubaria de quem o tem). É o que permite
     * ao robô ceder a vez ao usuário em vez de disputar e falhar em cascata. Cobre as 8
     * operações que passam pelo lock, não só o envio.
     */
    public static function operacaoEmAndamento(int $codnotafiscal): bool
    {
        return Cache::has("nfephp-ocupado-nf-{$codnotafiscal}");
    }

    /**
     * Executa uma chamada à SEFAZ com retry para erros transitórios de rede/TLS,
     * registrando CADA TENTATIVA na tblsefazcomunicacao.
     *
     * Mitiga o "unexpected eof while reading" do OpenSSL 3.x quando servidores
     * SEFAZ encerram a conexão sem o close_notify do TLS, além de timeouts e
     * resets eventuais. Outras SoapExceptions (HTTP 500, XML inválido, etc.)
     * propagam imediatamente — só vale retentar erro de transporte.
     *
     * É público porque é o funil de TODA conversa com a SEFAZ do projeto — as 20
     * chamadas, incluindo MDF-e, DFe, manifestação e consulta de cadastro. Antes
     * metade delas chamava $tools->sefazX() direto: ficavam sem retry (sofrendo do
     * mesmo bug de TLS que a NFe já contornava) e fora do log.
     *
     * O log entra aqui, e não no chamador, porque é aqui que se sabe o número da
     * tentativa — e é justamente o retry que se quer enxergar depois.
     */
    public static function chamarSefazComRetry(
        callable $fn,
        string $operacao,
        $tools = null,
        ?Filial $filial = null,
        ?NotaFiscal $nf = null
    ) {
        $atrasosMs = [500, 1500];
        $tentativa = 0;
        $filial = $filial ?? $nf?->Filial;

        while (true) {
            // Abre o registro ANTES da chamada: durante os ate 40s de conversa a tela ja
            // mostra a tentativa em andamento, e se o processo morrer no meio o registro
            // sobrevive como evidencia de que a tentativa existiu.
            $idLog = ($filial !== null)
                ? SefazLogService::iniciar($filial, $operacao, $tentativa + 1, $nf)
                : null;

            $inicio = microtime(true);
            try {
                $resp = $fn();
                static::registrarConversa($idLog, $filial, $operacao, $tentativa + 1, $inicio, true, $tools, $nf);
                return $resp;
            } catch (SoapException $e) {
                static::registrarConversa(
                    $idLog,
                    $filial,
                    $operacao,
                    $tentativa + 1,
                    $inicio,
                    false,
                    $tools,
                    $nf,
                    $e->getMessage()
                );

                if (!static::ehErroTransitorioSefaz($e) || $tentativa >= count($atrasosMs)) {
                    throw $e;
                }
                $espera = $atrasosMs[$tentativa];
                $tentativa++;
                Log::warning("SEFAZ {$operacao} falhou (tentativa {$tentativa}, retry em {$espera}ms): " . $e->getMessage());
                usleep($espera * 1000);
            }
        }
    }

    /**
     * Registra a tentativa e avalia contingência.
     *
     * A avaliação de contingência fica FORA do try/catch do SefazLogService de
     * propósito: o log pode falhar em silêncio, a decisão de contingência não pode.
     * Se estivesse dentro, uma exceção nela seria engolida e a contingência nunca
     * ativaria, sem ninguém perceber.
     */
    protected static function registrarConversa(
        ?int $idLog,
        ?Filial $filial,
        string $operacao,
        int $tentativa,
        float $inicio,
        bool $sucesso,
        $tools,
        ?NotaFiscal $nf,
        ?string $erro = null
    ): void {
        if ($filial === null) {
            return;
        }

        $duracaoms = (microtime(true) - $inicio) * 1000;
        SefazLogService::finalizar($idLog, $filial, $operacao, $tentativa, $duracaoms, $sucesso, $tools, $nf, $erro);

        if (ContingenciaService::operacaoRelevante($operacao)) {
            ContingenciaService::avaliar($filial->Empresa);
        }
    }

    protected static function ehErroTransitorioSefaz(SoapException $e): bool
    {
        // libcurl: 7=connect failed, 28=timeout, 35=ssl connect, 52=empty reply, 56=recv error
        if (in_array((int) $e->getCode(), [7, 28, 35, 52, 56], true)) {
            return true;
        }
        return (bool) preg_match(
            '/unexpected eof|SSL_read|Connection reset|Operation timed out|Resolving timed out|SSL connect error|Empty reply/i',
            $e->getMessage()
        );
    }

    public static function sefazStatus(Filial $filial)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $resp = static::chamarSefazComRetry(fn() => $tools->sefazStatus(), 'status', $tools, $filial);
        $st = new Standardize();
        $r = $st->toStd($resp);
        return $r;
    }

    public static function cscConsulta(Filial $filial)
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model('65');
        $resp = static::chamarSefazComRetry(fn() => $tools->sefazCsc(1), 'csc', $tools, $filial);
        $st = new Standardize();
        $r = $st->toStd($resp);
        return $r;
    }

    public static function criar(NotaFiscal $nf, $offline = null)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // Recriar o XML de uma nota que ja saiu do fluxo de emissao sobrescreveria o
        // -assinado.xml com os dados ATUAIS da nota, deixando-o divergente do -proc.xml que
        // a SEFAZ autorizou. Para recriar de verdade, limpe a autorizacao antes.
        if (!in_array($nf->status, [NotaFiscalStatusService::STATUS_DIGITACAO, NotaFiscalStatusService::STATUS_ERRO])) {
            $label = NotaFiscalStatusService::STATUS_LABELS[$nf->status] ?? $nf->status;
            throw new \Exception("Não é possível criar o XML de uma nota fiscal {$label}!");
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial, '4.00', 'PL_010_V4');
        $tools->model($nf->modelo);

        // Cria Arquivo XML
        $xml = NFePHPMakeService::montarXml($nf, $offline);

        // Assina XML
        $xmlAssinado = $tools->signNFe($xml);

        // Grava o XML assinado
        $nf = $nf->fresh();
        $path = NFePHPPathService::pathNFeAssinada($nf, true);
        file_put_contents($path, $xmlAssinado);

        // Retorna Resultado do processo
        return $xmlAssinado;
    }

    public static function enviar(NotaFiscal $nf)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial, '4.00', 'PL_010_V4');
        $tools->model($nf->modelo);

        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Carrega Arquivo XML Assinado
        $path = NFePHPPathService::pathNFeAssinada($nf);
        if (!file_exists($path)) {
            throw new \Exception("Arquivo da NFe não localizado ($path)!", static::EXCEPTION_XML_ASSINADO_INEXISTENTE);
        }
        $xmlAssinado = file_get_contents($path);

        // Monta Configuracao do Lote
        $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

        // Envia Lote para Sefaz
        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazEnviaLote([$xmlAssinado], $idLote),
            "enviaLote",
            $tools,
            null,
            $nf
        );
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat
        if (isset($respStd->cStat)) {

            // Se Lote Recebido Com Sucesso
            if ($respStd->cStat == 103) {

                // Salva Numero do Protocolo na tabela de Nota Fiscal
                $nf->nfereciboenvio = $respStd->infRec->nRec;
                $nf->nfedataenvio = Carbon::parse($respStd->dhRecbto);
                $nf->save(); // Dispara observers
                $sucesso = true;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfereciboenvio' => $nf->nfereciboenvio,
            'nfedataenvio' => ($nf->nfedataenvio) ? $nf->nfedataenvio->toW3cString() : null,
            'resp' => $resp,
        ];
    }


    public static function enviarSincrono(NotaFiscal $nf)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial, '4.00', 'PL_010_V4');
        $tools->model($nf->modelo);

        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Carrega Arquivo XML Assinado
        $path = NFePHPPathService::pathNFeAssinada($nf);
        if (!file_exists($path)) {
            throw new \Exception("Arquivo da NFe não localizado ($path)!", static::EXCEPTION_XML_ASSINADO_INEXISTENTE);
        }
        $xmlAssinado = file_get_contents($path);

        // Monta Configuracao do Lote
        $idLote = str_pad(1, 15, '0', STR_PAD_LEFT);

        // Envia Lote para Sefaz
        //$tools->timeout(5);
        $resp = null;
        $erroEnvio = null;
        try {
            $resp = static::chamarSefazComRetry(
                fn() => $tools->sefazEnviaLote([$xmlAssinado], $idLote, 1),
                "enviaLote",
                $tools,
                null,
                $nf
            );
        } catch (SoapException $e) {
            // Erro nao transitorio (XML invalido, HTTP 500, etc.): a nota nao foi
            // processada pela SEFAZ — propaga direto.
            if (!static::ehErroTransitorioSefaz($e)) {
                throw $e;
            }
            // Erro transitorio (ex: SSL 'unexpected eof') e SEM resposta: a 1a requisicao
            // PODE ter autorizado a nota na SEFAZ enquanto a resposta se perdia. Guarda o
            // erro e tenta recuperar por consulta abaixo; se nao recuperar, relanca no fim.
            $erroEnvio = $e;
            Log::warning("enviarSincrono NF#{$nf->codnotafiscal}: envio falhou no transporte, tentara recuperar por consulta: " . $e->getMessage());
        }

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        if ($resp !== null) {
            $st = new Standardize();
            $respStd = $st->toStd($resp);

            // Se veio cStat do Protocolo (modo sincrono encapsula o resultado em protNFe,
            // inclusive rejeicoes como a duplicidade 204)
            if (isset($respStd->protNFe->infProt->cStat)) {

                // Processa Protocolo para saber se foi autorizada
                $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
                $nf = $nf->fresh();

                // joga mensagem recebida da Sefaz para Variaveis de Retorno
                $cStat = $respStd->protNFe->infProt->cStat;
                $xMotivo = $respStd->protNFe->infProt->xMotivo;

                Log::info("enviarSincrono NF#{$nf->codnotafiscal}: protocolo recebido cStat={$cStat} ({$xMotivo}) autorizada=" . ($sucesso ? 'sim' : 'nao'));

                // Se veio cStat na Raiz (rejeicao geral, sem protocolo)
            } elseif (isset($respStd->cStat)) {
                $cStat = $respStd->cStat;
                $xMotivo = $respStd->xMotivo;

                Log::info("enviarSincrono NF#{$nf->codnotafiscal}: rejeicao raiz cStat={$cStat} ({$xMotivo})");
            } else {
                Log::warning("enviarSincrono NF#{$nf->codnotafiscal}: resposta da SEFAZ sem cStat reconhecivel");
            }
        }

        // Recuperacao por consulta quando a nota PODE ter sido autorizada mas nao temos o
        // protocolo. Dois gatilhos, mesma causa raiz (perda de conexao 'unexpected eof'):
        //  (a) a SEFAZ respondeu duplicidade (204/539) — o retry caiu em duplicidade
        //      porque a 1a requisicao ja havia autorizado; ou
        //  (b) o envio falhou no transporte sem resposta nenhuma — a 1a requisicao pode
        //      ter autorizado enquanto a resposta se perdia.
        // Em ambos o protocolo de autorizacao nao veio; buscamos consultando a chave.
        if (!$sucesso && ($erroEnvio !== null || static::ehDuplicidade($cStat))) {

            // Espera curta antes de consultar: apos autorizar, a SEFAZ leva um instante
            // para replicar a nota ao servico de consulta. Consulta imediata pode voltar
            // 217 (NFe nao consta) mesmo a nota tendo acabado de ser autorizada.
            usleep(500 * 1000);
            $motivo = ($erroEnvio !== null) ? 'falha no envio' : 'duplicidade';
            Log::info("enviarSincrono NF#{$nf->codnotafiscal}: {$motivo} -> consultando chave para recuperar autorizacao");

            // Reusa o nucleo de consultar() (sem lock — ja seguramos o lock aqui).
            // Best-effort: se a consulta tambem falhar, a nota fica para o robo
            // (NFePHPRoboService) resolver depois.
            try {
                $resConsulta = static::consultarSemLock($nf);
                $nf = $nf->fresh();
                if (!empty($nf->nfeautorizacao)) {
                    $sucesso = true;
                    $cStat = $resConsulta->cStat;
                    $xMotivo = $resConsulta->xMotivo;
                }
                Log::info("enviarSincrono NF#{$nf->codnotafiscal}: consulta de recuperacao cStat={$resConsulta->cStat} ({$resConsulta->xMotivo}) recuperada=" . (!empty($nf->nfeautorizacao) ? 'sim' : 'nao'));
            } catch (\Exception $e) {
                Log::warning("enviarSincrono NF#{$nf->codnotafiscal}: falha ao recuperar autorizacao por consulta: " . $e->getMessage());
            }
        }

        // Se o envio falhou no transporte e nao conseguimos recuperar a autorizacao,
        // propaga o erro original — a nota realmente nao foi autorizada (usuario reenvia,
        // robo resolve depois).
        if (!$sucesso && $erroEnvio !== null) {
            throw $erroEnvio;
        }

        // atualiza status
        $nf->update(['status' => NotaFiscalStatusService::calcularStatus($nf)]);

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'resp' => $resp
        ];
    }


    /**
     * Indica se o cStat da SEFAZ representa duplicidade da NFe (a nota ja existe na
     * base da SEFAZ).
     *
     * 204 - Duplicidade de NF-e (mesma chave ja autorizada) -> recuperavel por consulta.
     * 539 - Duplicidade de NF-e com diferenca na chave de acesso (o numero ja foi usado
     *       por OUTRA nota). Aqui a consulta pela nossa chave retorna "nao consta" e a
     *       recuperacao falha de proposito, sem autorizar nada indevido.
     */
    protected static function ehDuplicidade($cStat): bool
    {
        return in_array((int) $cStat, [204, 539], true);
    }

    public static function vincularProtocoloAutorizacao(NotaFiscal $nf, $protNFe, $resp)
    {

        // Verifica se tem o infProt
        if (!isset($protNFe->infProt)) {
            return false;
        }
        $infProt = $protNFe->infProt;

        // Le ANTES de sobrescrever: o e-mail sai na TRANSICAO para autorizada, e nao toda
        // vez que o protocolo e revinculado. Sem isso, clicar Consultar duas vezes numa nota
        // ja autorizada mandaria dois e-mails — nao existe coluna de controle no banco.
        $jaAutorizada = !empty($nf->nfeautorizacao);

        // Guarda no Banco de Dados informação da Autorização
        $nf->nfeautorizacao = $infProt->nProt;
        $nf->nfedataautorizacao = Carbon::parse($infProt->dhRecbto);
        $nf->save(); // Dispara observers

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = NFePHPPathService::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resp);


        // Salva o Arquivo com a NFe Aprovada
        $pathAprovada = NFePHPPathService::pathNFeAutorizada($nf, true);
        file_put_contents($pathAprovada, $xmlProtocolado);

        // So depois do XML em disco: o NFeAutorizadaMail anexa esse arquivo e estoura se
        // faltar. Este e o unico disparo de e-mail de NFe autorizada do sistema.
        if (!$jaAutorizada) {
            NFePHPMailJob::dispatch($nf->codnotafiscal)->onQueue('low');
        }

        return true;
    }

    public static function vincularProtocoloDenegacao(NotaFiscal $nf, $protNFe, $resp)
    {
        // Verifica se tem o infProt
        if (!isset($protNFe->infProt)) {
            return false;
        }
        $infProt = $protNFe->infProt;

        // Guarda no Banco de Dados informação da Autorização
        $nf->justificativa = $infProt->xMotivo;
        $nf->nfeinutilizacao = $infProt->nProt;
        $nf->nfedatainutilizacao = Carbon::parse($infProt->dhRecbto);
        $nf->save(); // Dispara observers

        // Carrega o Arquivo com o XML Assinado
        $pathAssinada = NFePHPPathService::pathNFeAssinada($nf);
        $xmlAssinado = file_get_contents($pathAssinada);

        // Vincula o Protocolo no XML Assinado
        $xmlProtocolado = Complements::toAuthorize($xmlAssinado, $resp);

        // Salva o Arquivo com a NFe Aprovada
        $pathNFeDenegada = NFePHPPathService::pathNFeDenegada($nf, true);
        file_put_contents($pathNFeDenegada, $xmlProtocolado);

        return true;
    }

    public static function vincularProtocoloCancelamento(NotaFiscal $nf, $procEventoNFe, $resp, $justificativa = null)
    {
        // Verifica se tem o infEvento
        if (!isset($procEventoNFe->retEvento->infEvento)) {
            return false;
        }
        $infEvento = $procEventoNFe->retEvento->infEvento;

        if (isset($procEventoNFe->evento->infEvento->detEvento->xJust)) {
            $justificativa = $procEventoNFe->evento->infEvento->detEvento->xJust;
        }

        // Guarda no Banco de Dados informação da Autorização
        // Trunca em 200 (limite varchar do banco) — defensivo caso SEFAZ retorne texto maior.
        $nf->justificativa = mb_substr((string) $justificativa, 0, 200);
        $nf->nfecancelamento = $infEvento->nProt;
        $nf->nfedatacancelamento = Carbon::parse($infEvento->dhRegEvento);
        $nf->save(); // Dispara observers

        // Monta o XML do cancelamento: a NFe autorizada com o <retEvento> anexado.
        //
        // O cancelRegister serve aos DOIS caminhos que chegam aqui — ele so procura um
        // <retEvento> com chNFe casando, e tanto o retEnvEvento (cancelar) quanto o
        // retConsSitNFe (consultar) tem isso dentro. Antes o caminho da consulta gravava o
        // $resp cru, que e o envelope SOAP inteiro, SEM a nota: um XML que a DANFE nao
        // renderiza e que o contador baixava no lugar do documento. Pior, ele sobrescrevia
        // o arquivo bom assim que qualquer consulta rodasse depois do cancelamento.
        $pathAutorizada = NFePHPPathService::pathNFeAutorizada($nf);
        if (!file_exists($pathAutorizada)) {
            // Sem o autorizado nao ha o que montar. Gravar o $resp cru so recriaria o lixo —
            // e a resposta da SEFAZ ja fica registrada em tblsefazcomunicacao e em conversas/.
            Log::warning("NF#{$nf->codnotafiscal}: XML autorizado inexistente, -cancelado.xml nao gravado ($pathAutorizada)");
            return true;
        }

        $xmlProtocolado = Complements::cancelRegister(file_get_contents($pathAutorizada), $resp);

        // Salva o Arquivo com a NFe Cancelada
        $pathNFeCancelada = NFePHPPathService::pathNFeCancelada($nf, true);
        file_put_contents($pathNFeCancelada, $xmlProtocolado);

        return true;
    }

    public static function consultarRecibo(NotaFiscal $nf)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // valida se existe Chave da NFe
        if (empty($nf->nfereciboenvio)) {
            throw new \Exception('Esta NFe não possui número de Recibo para ser consultado!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // Busca na sefaz status do recibo
        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazConsultaRecibo($nf->nfereciboenvio),
            "consultaRecibo",
            $tools,
            null,
            $nf
        );
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->protNFe->infProt->cStat)) {

            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'resp' => $resp
        ];
    }

    public static function cancelar(NotaFiscal $nf, $justificativa)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($justificativa));
        if (strlen($justificativa) < 15) {
            throw new \Exception('A justificativa deve ter pelo menos 15 caracteres!');
        }
        if (strlen($justificativa) > 200) {
            throw new \Exception('A justificativa deve ter no máximo 200 caracteres!');
        }

        // Valida Autorização
        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta nota ainda não está autorizada! Impossível prosseguir com o Cancelamento!');
        }

        // Valida Cancelamento
        // if (!empty($nf->nfecancelamento)) {
        //     throw new \Exception('Esta nota já está Cancelada! Impossível prosseguir com o Cancelamento!');
        // }

        // Valida Inutilizacao
        // if (!empty($nf->nfeinutilizacao)) {
        //     throw new \Exception('Esta nota já está Inutilizada! Impossível prosseguir com o Cancelamento!');
        // }

        // Instancia Tools para a configuracao e certificado
        // if ($nf->modelo == 65) {
        //     $tools = NFePHPConfigService::instanciaTools($nf->Filial, '3.10');
        // } else {
        //     $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        // }
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);

        $tools->model($nf->modelo);

        // solicita a sefaz cancelamento
        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazCancela($nf->nfechave, $justificativa, $nf->nfeautorizacao),
            "cancela",
            $tools,
            null,
            $nf
        );
        // return $resp;
        $st = new Standardize();
        $respStd = $st->toStd($resp);
        Log::info("Cancelamento NF {$nf->codnotafiscal}!", (array) $respStd);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';

        // Se veio cStat do Protocolo
        if (isset($respStd->retEvento->infEvento->cStat)) {

            // Processa Retorno do Evento
            $sucesso = static::processarEventoCancelamento($nf, $respStd, $resp, $justificativa);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->retEvento->infEvento->cStat;
            $xMotivo = $respStd->retEvento->infEvento->xMotivo;
        } elseif (isset($respStd->cStat)) {
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'nfecancelamento' => $nf->nfecancelamento,
            'nfedatanfecancelamento' => ($nf->nfedatanfecancelamento) ? $nf->nfedatanfecancelamento->toW3cString() : null,
            'resp' => $resp
        ];
    }

    /**
     * Inutiliza uma FAIXA de numeracao na SEFAZ.
     *
     * Nao recebe NotaFiscal de proposito: inutilizacao e ato sobre um intervalo, que pode
     * cobrir numeros que nunca tiveram nota. Quem orquestra (persistencia, marcacao das
     * notas da faixa, gravacao do XML) e o InutilizacaoService.
     *
     * Devolve os dados crus da resposta; nao grava nada no banco.
     */
    public static function inutilizar(
        Filial $filial,
        int $modelo,
        int $serie,
        int $numeroInicial,
        int $numeroFinal,
        string $justificativa
    ) {
        $guard = static::lock("inut-{$filial->codfilial}-{$modelo}-{$serie}-{$numeroInicial}-{$numeroFinal}");

        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($justificativa));
        if (strlen($justificativa) < 15) {
            throw new \Exception('A justificativa deve ter pelo menos 15 caracteres!');
        }
        if (strlen($justificativa) > 200) {
            throw new \Exception('A justificativa deve ter no máximo 200 caracteres!');
        }

        // Valida Pessoa Juridica
        if (!empty($filial->Pessoa->fisica)) {
            throw new \Exception('Impossível inutilizar numeração de Emitente Pessoa Física!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model($modelo);

        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazInutiliza($serie, $numeroInicial, $numeroFinal, $justificativa, $filial->nfeambiente),
            'inutiliza',
            $tools,
            $filial
        );
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';
        $protocolo = null;
        $protocolodata = null;
        $xml = null;

        if (isset($respStd->infInut->cStat)) {
            $cStat = $respStd->infInut->cStat;
            $xMotivo = $respStd->infInut->xMotivo;
            $protocolodata = isset($respStd->infInut->dhRecbto)
                ? Carbon::parse($respStd->infInut->dhRecbto)
                : null;

            // 102 - Inutilizacao de Numero Homologado
            if ($cStat == 102) {
                $sucesso = true;
                $protocolo = $respStd->infInut->nProt ?? null;
                $xml = Complements::toAuthorize($tools->lastRequest, $resp);

            // 256 - uma NF-e DA FAIXA ja esta inutilizada [nProt:...]
            // 563 - [+prot]ja existe um Pedido de inutilizacao igual
            //
            // Com faixa de 1 o protocolo devolvido E daquele numero, entao vale aproveitar
            // (era o comportamento antigo). Com faixa maior de 1 ele pertence a UM dos
            // numeros e nao a faixa — gravar na linha inteira seria falso, entao falha
            // pedindo para estreitar.
            } elseif (in_array($cStat, [256, 563])) {
                if ($numeroInicial != $numeroFinal) {
                    throw new \Exception(
                        "{$cStat} - {$xMotivo} Estreite a faixa: parte dela já está inutilizada na SEFAZ."
                    );
                }
                $regex = ($cStat == 256) ? '/\[nProt:(\d+)\]/' : '/\[\+(\d+)\]/';
                preg_match($regex, $xMotivo, $matches);
                $protocolo = $matches[1] ?? null;
                $sucesso = !empty($protocolo);
            }
        }

        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'protocolodata' => $protocolodata,
            'xml' => $xml,
            'resp' => $resp,
        ];
    }

    public static function cartaCorrecao(NotaFiscal $nf, $texto)
    {
        $guard = static::lockDaNotaFiscal($nf);

        // Valida Justificativa
        $justificativa = Strings::replaceSpecialsChars(trim($texto));
        if (strlen($justificativa) < 15) {
            throw new \Exception('O Texto deve ter pelo menos 15 caracteres!');
        }
        if (strlen($justificativa) > 200) {
            throw new \Exception('O Texto deve ter no máximo 200 caracteres!');
        }

        // Valida Inutilizacao
        if ($nf->modelo == NotaFiscalService::MODELO_NFCE) {
            throw new \Exception('Não é permitido Carta de Correção para NFCe!');
        }

        // Valida Inutilizacao
        if (!empty($nf->nfeinutilizacao)) {
            throw new \Exception('Esta nota já está Inutilizada!');
        }

        // Valida Autorização
        if (empty($nf->nfeautorizacao)) {
            throw new \Exception('Esta nota não está autorizada!');
        }

        // Valida Cancelamento
        if (!empty($nf->nfecancelamento)) {
            throw new \Exception('Esta nota já está Cancelada!');
        }

        // Instancia Tools para a configuracao e certificado
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // solicita a sefaz cancelamento
        $nSeqEvento = NotaFiscalCartaCorrecao::where('codnotafiscal', $nf->codnotafiscal)->max('sequencia');
        $nSeqEvento++;
        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazCCe($nf->nfechave, $justificativa, $nSeqEvento),
            "cce",
            $tools,
            null,
            $nf
        );
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        // inicializa variaveis para retorno
        $sucesso = false;
        $cStat = null;
        $xMotivo = 'Falha Comunicação SEFAZ!';
        $protocolo = null;
        $protocolodata = null;

        // Se veio cStat do Protocolo
        if (isset($respStd->retEvento->infEvento->cStat)) {

            // Se Carta Correcao
            // 135 -	Evento registrado e vinculado a NF-e
            // 136 -	Evento registrado, mas não vinculado a NF-e
            if (in_array($respStd->retEvento->infEvento->cStat, [135, 136])) {

                // Salva Carta de Correcao no Banco de Dados
                NotaFiscalCartaCorrecao::create([
                    'codnotafiscal' => $nf->codnotafiscal,
                    'sequencia' => $respStd->retEvento->infEvento->nSeqEvento,
                    'lote' => $respStd->retEvento->infEvento->nSeqEvento,
                    'data' => Carbon::parse($respStd->retEvento->infEvento->dhRegEvento),
                    'texto' => $texto,
                    'protocolo' => $respStd->retEvento->infEvento->nProt,
                    'protocolodata' => Carbon::parse($respStd->retEvento->infEvento->dhRegEvento),
                ]);

                // Salva arquivo XML com retorno
                $xml = Complements::toAuthorize($tools->lastRequest, $resp);
                $pathCartaCorrecao = NFePHPPathService::pathCartaCorrecao(
                    $nf,
                    (int) $respStd->retEvento->infEvento->nSeqEvento,
                    true
                );
                file_put_contents($pathCartaCorrecao, $xml);

                // Variaveis de retorno
                $sucesso = true;
                $protocolo = $respStd->retEvento->infEvento->nProt;
                $protocolodata = $respStd->retEvento->infEvento->dhRegEvento;
            }

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->retEvento->infEvento->cStat;
            $xMotivo = $respStd->retEvento->infEvento->xMotivo;
        }

        // Retorna Resultado do processo
        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'protocolo' => $protocolo,
            'protocolodata' => $protocolodata,
            'resp' => $resp
        ];
    }

    public static function processarProtocolo(NotaFiscal $nf, $protNFe, $resp)
    {

        // Se Autorizado
        // 100 - Autorizado o uso da NF-e
        // 150 - Autorizado o uso da NF-e, autorizacao fora de prazo
        if (in_array($protNFe->infProt->cStat, [100, 150])) {
            return static::vincularProtocoloAutorizacao($nf, $protNFe, $resp);
        }

        // Se Denegada
        // 301 Uso Denegado: Irregularidade fiscal do emitente
        // 302 Uso Denegado: Irregularidade fiscal do destinatário
        // 303 Uso Denegado: Destinatario nao habilitado a operar na UF
        if (in_array($protNFe->infProt->cStat, [301, 302, 303])) {
            static::vincularProtocoloDenegacao($nf, $protNFe, $resp);
            return false;
        }

        return false;
    }

    public static function processarEventoCancelamento(NotaFiscal $nf, $procEventoNFe, $resp, $justificativa = null)
    {

        // Se Autorizado
        // 101 - Cancelamento de NFe Homologado
        // 135 - Evento registrado e vinculado A NFe
        // 155 - Cancelamento Homologado Fora de Prazo
        if (in_array($procEventoNFe->retEvento->infEvento->cStat, [101, 135, 155])) {
            static::vincularProtocoloCancelamento($nf, $procEventoNFe, $resp, $justificativa);
            return true;
        }

        return false;
    }

    public static function consultar(NotaFiscal $nf)
    {
        $guard = static::lockDaNotaFiscal($nf);

        return static::consultarSemLock($nf);
    }

    /**
     * Nucleo da consulta de chave na SEFAZ, SEM adquirir lock.
     *
     * Separado de consultar() para ser reaproveitado por quem JA segura o lock da
     * nota (ex: enviarSincrono na recuperacao por duplicidade) — o lock nao e
     * reentrante, entao chamar consultar() de dentro do envio lancaria excecao.
     */
    protected static function consultarSemLock(NotaFiscal $nf)
    {
        // valida se existe Chave da NFe
        if (empty($nf->nfechave)) {
            throw new \Exception('Chave da NFe ausente!', static::EXCEPTION_CHAVE_NFE_AUSENTE);
        }

        // Instancia Tools para a configuracao e certificado
        // $tools = NFePHPConfigService::instanciaTools($nf->Filial, '3.10');
        $tools = NFePHPConfigService::instanciaTools($nf->Filial);
        $tools->model($nf->modelo);

        // consulta chave da NFe na sefaz
        $resp = static::chamarSefazComRetry(
            fn() => $tools->sefazConsultaChave($nf->nfechave, $nf->Filial->nfeambiente),
            "consultaChave",
            $tools,
            null,
            $nf
        );
        $st = new Standardize();
        $respStd = $st->toStd($resp);

        $sucesso = false;
        $cStat = null;
        $xMotivo = null;
        $tpEvento = null;

        // Verifica Retorno Principal
        if (isset($respStd->cStat)) {
            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->cStat;
            $xMotivo = $respStd->xMotivo;
        }

        // Se existe Protocolo de Autorizacao / Denegacao
        if (isset($respStd->protNFe->infProt->cStat)) {

            // Processa Protocolo para saber se foi autorizada
            $sucesso = static::processarProtocolo($nf, $respStd->protNFe, $resp);
            $nf = $nf->fresh();

            // joga mensagem recebida da Sefaz para Variaveis de Retorno
            $cStat = $respStd->protNFe->infProt->cStat;
            $xMotivo = $respStd->protNFe->infProt->xMotivo;
        }

        // se veio evento vinculado
        if (isset($respStd->procEventoNFe)) {

            // Se veio somente um evento, forca array de eventos
            if (!is_array($respStd->procEventoNFe)) {
                $respStd->procEventoNFe = [$respStd->procEventoNFe];
            }

            foreach ($respStd->procEventoNFe as $procEventoNFe) {

                // Cancelamento
                if (
                    isset($procEventoNFe->evento->infEvento->tpEvento) &&
                    $procEventoNFe->evento->infEvento->tpEvento == 110111
                ) {

                    // Processa Protocolo para saber se foi autorizada
                    $sucesso = static::processarEventoCancelamento($nf, $procEventoNFe, $resp);
                    $nf = $nf->fresh();

                    // joga mensagem recebida da Sefaz para Variaveis de Retorno
                    $tpEvento = $procEventoNFe->evento->infEvento->tpEvento;
                    $cStat = $procEventoNFe->retEvento->infEvento->cStat;
                    $xMotivo = $procEventoNFe->retEvento->infEvento->xMotivo;
                }

                // Carta de Correcao
                if (
                    isset($procEventoNFe->evento->infEvento->tpEvento) &&
                    $procEventoNFe->evento->infEvento->tpEvento == 110110
                ) {
                    $nfcc = NotaFiscalCartaCorrecao::firstOrNew([
                        'codnotafiscal' => $nf->codnotafiscal,
                        'sequencia' => $procEventoNFe->evento->infEvento->nSeqEvento
                    ]);
                    $nfcc->lote = $procEventoNFe->evento->infEvento->nSeqEvento;
                    $nfcc->data = Carbon::parse($procEventoNFe->retEvento->infEvento->dhRegEvento);
                    $nfcc->texto = $procEventoNFe->evento->infEvento->detEvento->xCorrecao;
                    $nfcc->protocolo = $procEventoNFe->retEvento->infEvento->nProt;
                    $nfcc->protocolodata = Carbon::parse($procEventoNFe->retEvento->infEvento->dhRegEvento);
                    $nfcc->save();
                }
            }
        }

        $nf->update(['status' => NotaFiscalStatusService::calcularStatus($nf)]);

        return (object) [
            'sucesso' => $sucesso,
            'cStat' => $cStat,
            'xMotivo' => $xMotivo,
            'tpEvento' => $tpEvento,
            'nfeautorizacao' => $nf->nfeautorizacao,
            'nfedataautorizacao' => ($nf->nfedataautorizacao) ? $nf->nfedataautorizacao->toW3cString() : null,
            'nfecancelamento' => $nf->nfecancelamento,
            'nfedatacancelamento' => ($nf->nfedatacancelamento) ? $nf->nfedatacancelamento->toW3cString() : null,
            'nfeinutilizacao' => $nf->nfeinutilizacao,
            'nfedatainutilizacao' => ($nf->nfedatainutilizacao) ? $nf->nfedatainutilizacao->toW3cString() : null,
            'justificativa' => $nf->justificativa,
            'resp' => $resp
        ];
    }

    public static function danfe(NotaFiscal $nf)
    {
        // Nota cancelada: prefere o -cancelado.xml, que e a NFe autorizada com o <retEvento>
        // anexado. As classes Danfe/Danfce acham esse evento sozinhas e carimbam "CANCELADA".
        //
        // Nao basta o arquivo existir: os gravados antes da correcao do
        // vincularProtocoloCancelamento podem ser o envelope SOAP da consulta, sem a nota
        // dentro — o Danfce lancaria "o xml do DANFE deve ser uma NFC-e modelo 65". Nesses
        // casos a DANFE sai do autorizado, sem tarja, como sempre saiu.
        $path = null;
        if (!empty($nf->nfecancelamento)) {
            $pathCancelada = NFePHPPathService::pathNFeCancelada($nf);
            if (file_exists($pathCancelada) && str_contains(file_get_contents($pathCancelada), '<infNFe')) {
                $path = $pathCancelada;
            }
        }

        // busca XML autorizado
        $path = $path ?? NFePHPPathService::pathNFeAutorizada($nf);
        if (!file_exists($path)) {

            // busca XML Assinado
            $path = NFePHPPathService::pathNFeAssinada($nf);
            if (!file_exists($path)) {
                throw new \Exception("Não foi Localizado o arquivo da NFe ($path)");
            }

            // Carrega XML Assinado
            $xml = file_get_contents($path);
            $st = new Standardize();
            $r = $st->toStd($xml);

            // Só deixa passar se for modo OffLine
            if (isset($r->infNFe->ide->tpEmis) && $r->infNFe->ide->tpEmis != Empresa::MODOEMISSAONFCE_OFFLINE) {
                throw new \Exception("Nota Fiscal ainda não está autorizada!");
            }
        } elseif (!file_exists($path)) {
            // Se arquivo XML Autorizado nao existir
            throw new \Exception("Não foi Localizado o arquivo da NFe ($path)");
        } else {
            // Carrega XML Autorizado
            $xml = file_get_contents($path);
        }

        // Logo somente na Migliorini
        $logo = null;
        if ($nf->Filial->codempresa == 1) {
            if ($nf->modelo == NotaFiscalService::MODELO_NFE) {
                $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(public_path('MGPapelariaLogo.jpeg')));
            } else {
                $logo = 'data://text/plain;base64,' . base64_encode(file_get_contents(public_path('MGPapelariaLogoSeloPretoBranco.jpeg')));
            }
        }

        // Instancia DANFE (Modelo 55) ou DANFCE (Modelo65)
        if ($nf->modelo == NotaFiscalService::MODELO_NFE) {
            $danfe = new Danfe($xml);
            // margem de 5 pro chrome não cortar na impressao
            $danfe->printParameters('P', 'A4', 5, 5);
        } else {
            $danfe = new Danfce($xml);
            $danfe->setMargins(3);
            // Imprime somente via CLiente quando offline
            $danfe->setOffLineDoublePrint(false);
        }

        // Helvetica pq Times muito ruim de ler
        $danfe->setDefaultFont('helvetica');

        // Nossa propaganda
        $danfe->creditsIntegratorFooter('MGsis - Powered by NFePHP');

        // Gera PDF
        $pdf = $danfe->render($logo);

        // Salva PDF
        $pathDanfe = NFePHPPathService::pathDanfe($nf, true);
        file_put_contents($pathDanfe, $pdf);

        // Quebra o PDF em várias páginas se necessário
        if ($nf->modelo == NotaFiscalService::MODELO_NFCE) {
            static::quebraPdfDanfePaginas($pathDanfe);
        }

        // retorna o caminho do PDF
        return $pathDanfe;
    }

    public static function quebraPdfDanfePaginas(string $pathDanfe): void
    {
        if (!file_exists($pathDanfe)) {
            throw new \RuntimeException("PDF não encontrado: {$pathDanfe}");
        }

        $alturaMaxMm = 297.0; // A4

        $tempDir = storage_path('app/mpdf');
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0775, true);
        }

        // Lê dimensões da página de origem via mPDF/FPDI (sem binários externos)
        $mpdfLeitor = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'orientation' => 'P',
            'margin_left' => 0, 'margin_right' => 0,
            'margin_top' => 0, 'margin_bottom' => 0,
            'tempDir' => $tempDir,
        ]);
        $mpdfLeitor->setSourceFile($pathDanfe);
        $tplIdx = $mpdfLeitor->importPage(1);
        $size = $mpdfLeitor->getTemplateSize($tplIdx); // ['width' => mm, 'height' => mm]

        $larguraMm = (float) $size['width'];
        $alturaMm  = (float) $size['height'];

        if (!$larguraMm || !$alturaMm) {
            throw new \RuntimeException('Não foi possível detectar o tamanho da página do PDF');
        }

        $paginasVerticais = (int) ceil($alturaMm / $alturaMaxMm);
        if ($paginasVerticais <= 1) {
            return;
        }

        // Re-renderiza em N páginas (largura da bobina x altura A4) usando o
        // mesmo template deslocado verticalmente — mPDF faz o clipping na página.
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => [$larguraMm, $alturaMaxMm],
            'orientation' => 'P',
            'margin_left' => 0, 'margin_right' => 0,
            'margin_top' => 0, 'margin_bottom' => 0,
            'tempDir' => $tempDir,
        ]);
        $mpdf->setSourceFile($pathDanfe);
        $tplIdx = $mpdf->importPage(1);

        for ($i = 0; $i < $paginasVerticais; $i++) {
            if ($i > 0) {
                $mpdf->AddPage();
            }
            $mpdf->useTemplate($tplIdx, 0, -$i * $alturaMaxMm, $larguraMm, $alturaMm);
        }

        $tmpOutput = $pathDanfe . '.tmp.pdf';
        $mpdf->Output($tmpOutput, \Mpdf\Output\Destination::FILE);

        if (!file_exists($tmpOutput)) {
            throw new \RuntimeException('Falha ao gerar PDF quebrado em páginas');
        }

        rename($tmpOutput, $pathDanfe);
    }

    public static function imprimir(NotaFiscal $nf, $impressora = null)
    {
        // Valida se o PDF existe
        $pathDanfe = NFePHPPathService::pathDanfe($nf, true);
        if (!file_exists($pathDanfe)) {
            static::danfe($nf);
        }
        if (!file_exists($pathDanfe)) {
            throw new \Exception("Impossível localizar arquivo PDF da DANFe!", 1);
        }

        // Valida se informado nome da Impressora
        if (empty($impressora) && !empty($nf->codusuariocriacao)) {
            $impressora = $nf->getRelationValue('UsuarioCriacao')?->impressoratermica;
        }
        if (empty($impressora)) {
            throw new \Exception("Impressora não informada!", 1);
        }

        // Executa comando de impressao
        $url = \URL::temporarySignedRoute('nota-fiscal.danfe', now()->addMinutes(10), ['codnotafiscal' => $nf->codnotafiscal]);
        $cmd = 'curl -X POST https://rest.ably.io/channels/printing/messages -u "' . config('services.ably.key') . '" -H "Content-Type: application/json" --data \'{ "name": "' . $impressora . '", "data": "{\"url\": \"' . $url . '\", \"method\": \"get\", \"options\": [\"fit-to-page\"], \"copies\": 1}" }\'';
        exec($cmd);

        // retorna
        return [
            "sucesso" => true,
            "mensagem" => "Danfe enviada para '$impressora'!",
            "commando" => $cmd
        ];
    }

    public static function xml(NotaFiscal $nf)
    {
        $path = null;
        if (!empty($nf->nfecancelamento)) {
            $path = NFePHPPathService::pathNFeCancelada($nf);
        }
        if ($path == null || !file_exists($path)) {
            $path = NFePHPPathService::pathNFeAutorizada($nf);
        }
        if (!file_exists($path)) {
            $path = NFePHPPathService::pathNFeAssinada($nf);
        }
        if (!file_exists($path)) {
            throw new \Exception("Não existe arquivo XML para esta Nota Fiscal!", 1);
        }
        return file_get_contents($path);
    }

    /**
     * Caminho de cada tipo de XML da nota, indexado pelo tipo que a API expoe.
     *
     * Sao 4 slots fixos (sobrescritos a cada nova versao do documento) mais uma entrada por
     * carta de correcao — a CCe e a unica que se acumula, por isso leva a sequencia no tipo.
     * As conversas com a SEFAZ NAO entram aqui: ja tem endpoint e tela proprios.
     */
    protected static function caminhosXml(NotaFiscal $nf): array
    {
        if (empty($nf->nfechave)) {
            return [];
        }

        $caminhos = [
            'assinado' => ['XML Assinado', NFePHPPathService::pathNFeAssinada($nf)],
            'autorizado' => ['XML Autorizado', NFePHPPathService::pathNFeAutorizada($nf)],
            'denegado' => ['XML Denegado', NFePHPPathService::pathNFeDenegada($nf)],
            'cancelado' => ['XML Cancelamento', NFePHPPathService::pathNFeCancelada($nf)],
        ];

        foreach ($nf->NotaFiscalCartaCorrecaoS as $cce) {
            $caminhos["cce-{$cce->sequencia}"] = [
                "Carta de Correção {$cce->sequencia}",
                NFePHPPathService::pathCartaCorrecao($nf, $cce->sequencia),
            ];
        }

        return $caminhos;
    }

    /**
     * Lista os XMLs que existem em disco para a nota. So devolve o que da para abrir —
     * quem chama nao precisa adivinhar nem tratar 404.
     */
    public static function listarXmls(NotaFiscal $nf): array
    {
        $xmls = [];

        foreach (static::caminhosXml($nf) as $tipo => [$label, $path]) {
            if (file_exists($path)) {
                $xmls[] = ['tipo' => $tipo, 'label' => $label];
            }
        }

        return $xmls;
    }

    public static function xmlPorTipo(NotaFiscal $nf, string $tipo): string
    {
        $caminhos = static::caminhosXml($nf);

        if (!isset($caminhos[$tipo])) {
            throw new \Exception("Tipo de XML desconhecido ({$tipo})!", 1);
        }

        [$label, $path] = $caminhos[$tipo];

        if (!file_exists($path)) {
            throw new \Exception("Não existe {$label} para esta Nota Fiscal!", 1);
        }

        return file_get_contents($path);
    }

    public static function sefazCadastro(Filial $filial, $uf = 'MT', $cnpj = '', $cpf = '', $iest = '')
    {
        $tools = NFePHPConfigService::instanciaTools($filial);
        $tools->model('55');
        $uf = $uf;
        $cnpj = $cnpj;
        $iest = $iest;
        $cpf = $cpf;
        $response = static::chamarSefazComRetry(
            fn() => $tools->sefazCadastro($uf, $cnpj, $iest, $cpf),
            'cadastro',
            $tools,
            $filial
        );
        $stdCl = (new Standardize($response))->toStd();
        return $stdCl;
    }
}
