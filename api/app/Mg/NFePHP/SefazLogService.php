<?php

namespace Mg\NFePHP;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Mg\Filial\Filial;
use Mg\NotaFiscal\NotaFiscal;

/**
 * Registra cada conversa com a SEFAZ: metadado na tblsefazcomunicacao e o par
 * request/response gzipado em disco.
 *
 * Sincrono de proposito. O destino e NVMe local (/opt/www/Arquivos), onde gzencode +
 * file_put_contents de ~15 KB e sub-milissegundo — nao compensa a complexidade de um job,
 * e assim o metadado nunca se perde se a fila estiver parada.
 *
 * A biblioteca ja entrega tudo de graca: $tools->soap e publico e o SoapBase expoe
 * requestHead/requestBody/responseHead/responseBody. Nao precisa interceptar curl.
 */
class SefazLogService
{
    /**
     * Grava a conversa. NUNCA lanca: uma falha de disco ou de banco aqui nao pode derrubar
     * uma emissao no balcao.
     *
     * Devolve o id da linha, ou null se o log falhou. Quem chama decide o que fazer depois
     * — em especial, a avaliacao de contingencia roda FORA daqui, justamente para que uma
     * excecao nela nao seja engolida por este try/catch.
     */
    public static function registrar(
        Filial $filial,
        string $operacao,
        int $tentativa,
        float $duracaoms,
        bool $sucesso,
        $tools = null,
        ?NotaFiscal $nf = null,
        ?string $erro = null
    ): ?int {
        try {
            $requestHead = $tools->soap->requestHead ?? null;
            $requestBody = $tools->soap->requestBody ?? ($tools->lastRequest ?? null);
            $responseHead = $tools->soap->responseHead ?? null;
            $responseBody = $tools->soap->responseBody ?? ($tools->lastResponse ?? null);

            $comunicacao = SefazComunicacao::create([
                'codfilial' => $filial->codfilial,
                'codnotafiscal' => $nf?->codnotafiscal,
                'operacao' => mb_substr($operacao, 0, 50),
                'ambiente' => $filial->nfeambiente,
                'tentativa' => $tentativa,
                'httpcode' => static::httpCode($responseHead),
                'cstat' => static::extrair('/<cStat>(\d+)<\/cStat>/', $responseBody, 4),
                'xmotivo' => static::extrair('/<xMotivo>(.*?)<\/xMotivo>/', $responseBody, 255),
                'duracaoms' => (int) round($duracaoms),
                'sucesso' => $sucesso,
                'erro' => $erro ? mb_substr($erro, 0, 500) : null,
                'codusuariocriacao' => static::codusuario(),
            ]);

            $arquivo = static::gravarConversa(
                $filial,
                $comunicacao->codsefazcomunicacao,
                $operacao,
                $nf?->nfechave,
                $requestHead,
                $requestBody,
                $responseHead,
                $responseBody
            );

            if ($arquivo !== null) {
                $comunicacao->arquivo = $arquivo;
                $comunicacao->save();
            }

            return $comunicacao->codsefazcomunicacao;
        } catch (\Throwable $e) {
            Log::warning("SefazLogService: falha ao registrar '{$operacao}': " . $e->getMessage());
            return null;
        }
    }

    /**
     * Grava o .gz e devolve o caminho relativo a NFE_PHP_PATH (null se falhou).
     */
    protected static function gravarConversa(
        Filial $filial,
        int $id,
        string $operacao,
        ?string $chave,
        ?string $requestHead,
        ?string $requestBody,
        ?string $responseHead,
        ?string $responseBody
    ): ?string {
        try {
            $conteudo = implode("\n", [
                '<!-- REQUEST -->',
                (string) $requestHead,
                (string) $requestBody,
                '<!-- RESPONSE -->',
                (string) $responseHead,
                (string) $responseBody,
            ]);

            $path = NFePHPPathService::pathConversa(
                $filial,
                $id,
                $operacao,
                $chave,
                Carbon::now(),
                true
            );

            if (file_put_contents($path, gzencode($conteudo, 6)) === false) {
                return null;
            }

            return ltrim(str_replace(rtrim(config('mg.paths.nfe_php'), '/'), '', $path), '/');
        } catch (\Throwable $e) {
            Log::warning("SefazLogService: falha ao gravar conversa #{$id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * cStat/xMotivo por regex barata: e so para filtrar e listar. A verdade completa esta
     * no .gz. Em resposta sincrona o primeiro cStat e o da raiz, o que basta aqui.
     */
    protected static function extrair(string $regex, ?string $corpo, int $tamanho): ?string
    {
        if (empty($corpo) || !preg_match($regex, $corpo, $m)) {
            return null;
        }
        return mb_substr(trim($m[1]), 0, $tamanho);
    }

    protected static function httpCode(?string $responseHead): ?int
    {
        if (empty($responseHead) || !preg_match('/HTTP\/[\d.]+\s+(\d{3})/', $responseHead, $m)) {
            return null;
        }
        return (int) $m[1];
    }

    protected static function codusuario(): ?int
    {
        foreach (['api', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user()->codusuario;
            }
        }
        return null;
    }
}
