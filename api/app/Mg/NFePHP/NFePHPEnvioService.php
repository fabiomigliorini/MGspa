<?php

namespace Mg\NFePHP;

use Illuminate\Support\Facades\Cache;

use Mg\NotaFiscal\NotaFiscal;
use Mg\NotaFiscal\NotaFiscalStatusService;

/**
 * Transmissão da NFe à SEFAZ, assíncrona.
 *
 * Faz UMA coisa: pega o XML assinado que já está em disco e o entrega à SEFAZ. Não cria, não
 * recria, não decide tpEmis — isso é do /criar. Quem encadeia criar + transmitir é o botão
 * Emitir, no front, que também é quem sabe que uma NFC-e em contingência off-line simplesmente
 * não passa por aqui (o robô de pendentes transmite depois, dentro das 24h).
 *
 * O e-mail não sai daqui: quem despacha o NFePHPMailJob é o vincularProtocoloAutorizacao do
 * NFePHPService, o único ponto onde a nota passa a autorizada — assim o e-mail também sai
 * para quem autoriza por fora deste job (botão Consultar, robô de pendentes, rotas legadas).
 *
 * POR QUE ASSÍNCRONO
 *
 * É a única ação de NFe que precisa ser. O axios dos apps tem timeout de 15s, mas
 * enviarSincrono leva até ~243s no pior caso (envio com 3 tentativas de 40s + consulta de
 * recuperação com outras 3). O cliente abortava aos 15s enquanto o PHP-FPM continuava rodando
 * e segurando o lock da nota — e o retry do usuário batia em "Outra operação já está em
 * andamento". Não era lock órfão: era o cliente desistindo 16× mais rápido que a operação.
 *
 * Criar/consultar/cancelar/inutilizar continuam síncronos: nenhuma delas chega perto disso.
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
     * Enfileira a transmissão. IDEMPOTENTE: se já houver uma em andamento, devolve o progresso
     * atual sem despachar nada.
     *
     * É essa idempotência que mata o "Outra operação já está em andamento" que o usuário
     * via ao reclicar: em vez de erro, o segundo clique se anexa à transmissão que já corre.
     */
    public static function iniciar(NotaFiscal $nf): array
    {
        if (static::emAndamento($nf->codnotafiscal)) {
            return static::progresso($nf->codnotafiscal);
        }

        static::validar($nf);

        // Sem chave não existe XML assinado para transmitir. Barra aqui para o erro sair no
        // POST, em vez de só aparecer no polling depois de o job falhar.
        if (empty($nf->nfechave)) {
            throw new \Exception('Nota fiscal sem chave de acesso: crie o XML antes de transmitir!');
        }

        $payload = static::gravar($nf->codnotafiscal, [
            'status' => 'processando',
            'etapa' => 'fila',
            'mensagem' => 'Na fila...',
            'sucesso' => null,
            'cStat' => null,
            'xMotivo' => null,
        ]);

        NFePHPEnviarJob::dispatch($nf->codnotafiscal)->onQueue('urgent');

        return $payload;
    }

    /**
     * Corpo do job. Grava o erro no progresso e RELANÇA, para o job aparecer em
     * tbljobsfailedspa e no log do worker.
     */
    public static function executar(int $codnotafiscal): void
    {
        $nf = NotaFiscal::findOrFail($codnotafiscal);

        try {
            static::etapa($codnotafiscal, 'transmitindo', 'Transmitindo para a SEFAZ...');
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
                'sucesso' => (bool) $res->sucesso,
                'cStat' => $res->cStat,
                'xMotivo' => $res->xMotivo,
            ]);
        } catch (\Throwable $e) {
            static::gravar($codnotafiscal, [
                'status' => 'erro',
                'etapa' => 'erro',
                'mensagem' => $e->getMessage(),
                'sucesso' => false,
                'cStat' => null,
                'xMotivo' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
