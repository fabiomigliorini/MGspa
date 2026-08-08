<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\Cache;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalService;
use Mg\NotaFiscal\NotaFiscalStatusService;

/**
 * Envio de NFe assíncrono: o "supermétodo" que faz criar + enviar.
 *
 * O e-mail saiu daqui: quem despacha o NFePHPMailJob é o vincularProtocoloAutorizacao do
 * NFePHPService, o único ponto onde a nota passa a autorizada — assim o e-mail também sai
 * para quem autoriza por fora deste job (botão Consultar, robô de pendentes, rotas legadas).
 *
 * POR QUE ASSÍNCRONO
 *
 * O axios dos apps tem timeout de 15s, mas enviarSincrono leva até ~243s no pior caso
 * (envio com 3 tentativas de 40s + consulta de recuperação com outras 3). O cliente
 * abortava aos 15s enquanto o PHP-FPM continuava rodando e segurando o lock da nota — e o
 * retry do usuário batia em "Outra operação já está em andamento". Não era lock órfão: era
 * o cliente desistindo 16× mais rápido que a operação.
 *
 * POR QUE O SUPERMÉTODO
 *
 * Antes o FRONT orquestrava: POST /criar -> parseava o XML com DOMParser para achar
 * <tpEmis> -> decidia se era contingência -> POST /enviar-sincrono -> POST /mail. Ou seja,
 * decisão fiscal tomada no navegador, em 3 round-trips. O backend já sabe tudo isso
 * (tpemis está persistido na nota).
 *
 * Progresso em Cache, no mesmo padrão de Mg\Rh\ReprocessarPeriodoService.
 */
class NFePHPEnvioService
{
    const CACHE_TTL = 3600;

    public static function chaveProgresso(int $codnotafiscal): string
    {
        return "nfe:envio:{$codnotafiscal}";
    }

    public static function progresso(int $codnotafiscal): ?array
    {
        return Cache::get(static::chaveProgresso($codnotafiscal));
    }

    public static function emAndamento(int $codnotafiscal): bool
    {
        $p = static::progresso($codnotafiscal);
        return !empty($p) && ($p['status'] ?? null) === 'processando';
    }

    protected static function gravar(int $codnotafiscal, array $payload): array
    {
        Cache::put(static::chaveProgresso($codnotafiscal), $payload, static::CACHE_TTL);
        return $payload;
    }

    protected static function etapa(int $codnotafiscal, string $etapa, string $mensagem): array
    {
        $atual = static::progresso($codnotafiscal) ?? [];
        return static::gravar($codnotafiscal, array_merge($atual, [
            'status' => 'processando',
            'etapa' => $etapa,
            'mensagem' => $mensagem,
        ]));
    }

    public static function validar(NotaFiscal $nf): void
    {
        if (empty($nf->emitida)) {
            throw new \Exception('Esta nota fiscal ainda não foi emitida!');
        }
        if (!in_array($nf->status, [NotaFiscalStatusService::STATUS_DIGITACAO, NotaFiscalStatusService::STATUS_ERRO])) {
            throw new \Exception("Não é possível enviar uma nota com status {$nf->status}!");
        }
    }

    /**
     * Enfileira o envio. IDEMPOTENTE: se já houver envio em andamento, devolve o progresso
     * atual sem despachar nada.
     *
     * É essa idempotência que mata o "Outra operação já está em andamento" que o usuário
     * via ao reclicar: em vez de erro, o segundo clique se anexa ao envio que já corre.
     *
     * $offline é tri-state: null = automático (segue a conf da empresa), true = força
     * contingência, false = força online.
     */
    public static function iniciar(NotaFiscal $nf, ?bool $offline = null): array
    {
        if (static::emAndamento($nf->codnotafiscal)) {
            return static::progresso($nf->codnotafiscal);
        }

        static::validar($nf);

        $payload = static::gravar($nf->codnotafiscal, [
            'status' => 'processando',
            'etapa' => 'fila',
            'mensagem' => 'Na fila...',
            'contingencia' => false,
            'sucesso' => null,
            'cStat' => null,
            'xMotivo' => null,
        ]);

        NFePHPEnviarJob::dispatch($nf->codnotafiscal, $offline)->onQueue('urgent');

        return $payload;
    }

    /**
     * Corpo do job. Grava o erro no progresso e RELANÇA, para o job aparecer em
     * tbljobsfailedspa e no log do worker.
     */
    public static function executar(int $codnotafiscal, ?bool $offline = null): void
    {
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        try {
            static::etapa($codnotafiscal, 'criando', 'Criando arquivo XML...');
            NFePHPService::criar($nf, $offline);
            $nf = $nf->fresh();

            // Contingência off-line: a nota NÃO vai à SEFAZ agora. O DANFE é impresso e o
            // robô transmite depois, dentro do prazo de 24h. O front usa esse flag para
            // decidir imprimir cupom e abrir a DANFE.
            if ($nf->tpemis == NotaFiscalService::TPEMIS_OFFLINE) {
                static::gravar($codnotafiscal, [
                    'status' => 'concluido',
                    'etapa' => 'concluido',
                    'mensagem' => 'NFC-e emitida em contingência off-line',
                    'contingencia' => true,
                    'sucesso' => true,
                    'cStat' => null,
                    'xMotivo' => 'Emitida em contingência off-line',
                ]);
                return;
            }

            static::etapa($codnotafiscal, 'enviando', 'Enviando para a SEFAZ...');
            $res = NFePHPService::enviarSincrono($nf);

            // O e-mail NAO e disparado aqui: quem despacha o NFePHPMailJob e o proprio
            // vincularProtocoloAutorizacao(), para cobrir tambem quem autoriza por fora
            // deste job (botao Consultar, robo de pendentes, rotas legadas). Tirar o e-mail
            // daqui tambem tira uma query do caminho critico — o 'concluido' abaixo e o que
            // o front espera para pintar a linha de verde.
            static::gravar($codnotafiscal, [
                'status' => 'concluido',
                'etapa' => 'concluido',
                'mensagem' => "{$res->cStat} - {$res->xMotivo}",
                'contingencia' => false,
                'sucesso' => (bool) $res->sucesso,
                'cStat' => $res->cStat,
                'xMotivo' => $res->xMotivo,
            ]);
        } catch (\Throwable $e) {
            static::gravar($codnotafiscal, [
                'status' => 'erro',
                'etapa' => 'erro',
                'mensagem' => $e->getMessage(),
                'contingencia' => false,
                'sucesso' => false,
                'cStat' => null,
                'xMotivo' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
