<?php

namespace Mg\NotaFiscalServico;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mg\Filial\CertificadoService;
use Mg\Filial\Filial;
use Mg\NFePHP\NFePHPPathService;
use NFePHP\Common\Certificate;
use NFePHP\Common\Signer;
use NFePHP\Common\Validator;

/**
 * Conversa com o Sistema Nacional da NFS-e (TASK-279): Sefin Nacional (emissao, consulta,
 * eventos). REST + JSON, com o certificado A1 da filial no TLS (mTLS) e os XMLs
 * assinados (XMLDSig rsa-sha1, como o resto do NFePHP), em gzip + base64.
 *
 * Certificado, assinatura e schema vem do sped-common; o NFePHP nao tem a parte de API.
 */
class NfseSefinService
{
    const URL_SEFIN = [
        1 => 'https://sefin.nfse.gov.br/sefinnacional',
        2 => 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional',
    ];

    // Distribuicao de DF-e (caixa do ADN)
    const URL_ADN = [
        1 => 'https://adn.nfse.gov.br',
        2 => 'https://adn.producaorestrita.nfse.gov.br',
    ];

    const TIMEOUT = 30;
    const CONNECT_TIMEOUT = 10;

    public static function ambiente(): int
    {
        return (int) config('mg.nfe_ambiente') == 1 ? 1 : 2;
    }

    /**
     * Leiaute da reforma tributaria (finNFSe, xDescServ no fim do cServ)? A homologacao
     * ja roda esse; a producao, so quando ligado no .env (NFSE_LEIAUTE_RTC).
     */
    public static function leiauteRtc(): bool
    {
        return static::ambiente() == 2 || (bool) config('mg.nfse_leiaute_rtc');
    }

    public static function certificado(Filial $filial): Certificate
    {
        return Certificate::readPfx(
            CertificadoService::pfxConteudo($filial),
            CertificadoService::pfxSenha($filial)
        );
    }

    /**
     * Assina e devolve COM a declaracao UTF-8: sem ela o Sistema Nacional recusa
     * (E1229 "Xml nao esta utilizando codificacao UTF-8").
     */
    public static function assinar(Filial $filial, string $xml, string $tag, string $raiz): string
    {
        $assinado = Signer::sign(static::certificado($filial), $xml, $tag, 'Id', OPENSSL_ALGO_SHA1, Signer::CANONICAL, $raiz);
        return '<?xml version="1.0" encoding="UTF-8"?>' . $assinado;
    }

    /**
     * Confere o XML contra o XSD oficial do leiaute em uso (resources/nfse/schemes).
     * Lanca com a lista de erros do libxml.
     */
    public static function validar(string $xml, string $schema): void
    {
        $pasta = static::leiauteRtc() ? 'restrita' : '1.01';
        Validator::isValid($xml, resource_path("nfse/schemes/{$pasta}/{$schema}"));
    }

    public static function gzipBase64(string $xml): string
    {
        return base64_encode(gzencode($xml));
    }

    /**
     * Base64 de gzip -> XML. A consulta de eventos devolve o gzip em base64 DUAS vezes
     * (arquivoXml = base64 de "H4sI..."): desembrulha ate achar o XML.
     */
    public static function ungzipBase64(?string $conteudo): ?string
    {
        for ($i = 0; $i < 3 && !empty($conteudo); $i++) {
            $conteudo = trim($conteudo);
            if (str_starts_with($conteudo, '<')) {
                return $conteudo;
            }
            $bin = base64_decode($conteudo, true);
            if ($bin === false) {
                return null;
            }
            // gzip comeca com 1f 8b
            $conteudo = str_starts_with($bin, "\x1f\x8b") ? @gzdecode($bin) : $bin;
            if ($conteudo === false) {
                return null;
            }
        }
        return (!empty($conteudo) && str_starts_with(trim($conteudo), '<')) ? trim($conteudo) : null;
    }

    /**
     * POST /nfse com a DPS assinada.
     */
    public static function enviarDps(NotaFiscalServico $nfs, string $idDps, string $xmlAssinado): array
    {
        return static::requisicao($nfs, $idDps, 'enviar', 'post', 'nfse', [
            'dpsXmlGZipB64' => static::gzipBase64($xmlAssinado),
        ]);
    }

    /**
     * GET /dps/{id}: se a DPS ja virou NFS-e, devolve a chave.
     */
    public static function consultarDps(NotaFiscalServico $nfs, string $idDps): array
    {
        return static::requisicao($nfs, $idDps, 'consultar-dps', 'get', "dps/{$idDps}");
    }

    /**
     * GET /nfse/{chave}: o XML da NFS-e.
     */
    public static function consultarNfse(NotaFiscalServico $nfs, string $idDps, string $chave): array
    {
        return static::requisicao($nfs, $idDps, 'consultar-nfse', 'get', "nfse/{$chave}");
    }

    /**
     * POST /nfse/{chave}/eventos com o pedido de registro de evento assinado.
     */
    public static function registrarEvento(NotaFiscalServico $nfs, string $idDps, string $xmlAssinado): array
    {
        return static::requisicao($nfs, $idDps, 'cancelar', 'post', "nfse/{$nfs->nfsechave}/eventos", [
            'pedidoRegistroEventoXmlGZipB64' => static::gzipBase64($xmlAssinado),
        ]);
    }

    /**
     * GET /nfse/{chave}/eventos/{tipo}/{sequencia}: o evento, se existir.
     */
    public static function consultarEvento(NotaFiscalServico $nfs, string $idDps, string $tipo, int $sequencia = 1): array
    {
        return static::requisicao($nfs, $idDps, 'consultar-evento', 'get', "nfse/{$nfs->nfsechave}/eventos/{$tipo}/{$sequencia}");
    }

    /**
     * Chamada a Sefin com o certificado da filial, gravando a conversa da nota.
     * Devolve ['status' => http, 'json' => array|null, 'corpo' => string]. Nao lanca por
     * status HTTP (quem chama interpreta); lanca por falha de conexao.
     */
    protected static function requisicao(
        NotaFiscalServico $nfs,
        string $idDps,
        string $operacao,
        string $metodo,
        string $rota,
        ?array $corpo = null
    ): array {
        $url = static::URL_SEFIN[static::ambiente()] . '/' . $rota;
        $path = fn () => NFePHPPathService::pathNfseConversa($nfs, $idDps, $operacao, true);
        return static::http($nfs->Filial, $metodo, $url, $corpo, $path);
    }

    /**
     * GET no ADN (distribuicao de DF-e) com o certificado da filial.
     */
    public static function getAdn(Filial $filial, string $rota, string $nomeConversa): array
    {
        $url = static::URL_ADN[static::ambiente()] . '/' . $rota;
        $path = fn () => NFePHPPathService::pathNfseConversaFilial($filial, $nomeConversa, true);
        return static::http($filial, 'get', $url, null, $path);
    }

    /**
     * HTTP com o certificado da filial no TLS (mTLS). O curl so le certificado e chave de
     * arquivo: grava temporarios e apaga no fim. A conversa (request + response) vai para
     * o arquivo que $pathConversa devolver.
     */
    protected static function http(Filial $filial, string $metodo, string $url, ?array $corpo, callable $pathConversa): array
    {
        $certificado = static::certificado($filial);
        $arqCert = tempnam(sys_get_temp_dir(), 'nfse-cert-');
        $arqKey = tempnam(sys_get_temp_dir(), 'nfse-key-');
        file_put_contents($arqCert, (string) $certificado->publicKey);
        file_put_contents($arqKey, (string) $certificado->privateKey);
        chmod($arqKey, 0600);

        $inicio = microtime(true);
        $resposta = null;
        $erro = null;
        try {
            $http = Http::withOptions([
                'cert' => $arqCert,
                'ssl_key' => $arqKey,
                'timeout' => static::TIMEOUT,
                'connect_timeout' => static::CONNECT_TIMEOUT,
                'http_errors' => false,
            ])->acceptJson();
            if ($metodo == 'post') {
                // Envio nunca repete sozinho: duplicaria nota ou evento
                $resposta = $http->asJson()->post($url, $corpo);
            } else {
                // Consulta repete uma vez se a conexao cair (o Sistema Nacional as vezes
                // derruba a conexao no handshake)
                try {
                    $resposta = $http->get($url);
                } catch (\Illuminate\Http\Client\ConnectionException $e) {
                    sleep(1);
                    $resposta = $http->get($url);
                }
            }
            return [
                'status' => $resposta->status(),
                'json' => $resposta->json(),
                'corpo' => $resposta->body(),
            ];
        } catch (\Throwable $e) {
            $erro = $e->getMessage();
            throw new \Exception("Falha de comunicação com o Sistema Nacional da NFS-e: {$erro}");
        } finally {
            @unlink($arqCert);
            @unlink($arqKey);
            static::registrarConversa($pathConversa, $metodo, $url, $corpo, $resposta, $erro, $inicio);
        }
    }

    /**
     * Guarda request + response em disco (arvore de conversas). Nunca lanca.
     */
    protected static function registrarConversa(callable $pathConversa, $metodo, $url, $corpo, $resposta, $erro, $inicio): void
    {
        try {
            $conteudo = json_encode([
                'metodo' => strtoupper($metodo),
                'url' => $url,
                'duracaoms' => round((microtime(true) - $inicio) * 1000),
                'request' => $corpo,
                'status' => $resposta?->status(),
                'response' => $resposta?->body(),
                'erro' => $erro,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            file_put_contents($pathConversa(), gzencode($conteudo));
        } catch (\Throwable $e) {
            Log::warning('NfseSefinService: falha ao gravar conversa: ' . $e->getMessage());
        }
    }

    /**
     * Junta as mensagens de erro/alerta da resposta numa linha legivel.
     * O Sistema Nacional usa ora Codigo/Descricao/Complemento, ora minusculas.
     */
    public static function mensagens(?array $json, string $chave = 'erros'): string
    {
        if (empty($json)) {
            return '';
        }
        $lista = $json[$chave] ?? $json[ucfirst($chave)] ?? null;
        if (empty($lista) && $chave == 'erros') {
            $lista = $json['erro'] ?? $json['Erro'] ?? null;
            if (!empty($lista) && !isset($lista[0])) {
                $lista = [$lista];
            }
        }
        if (empty($lista) || !is_array($lista)) {
            return '';
        }
        $linhas = [];
        foreach ($lista as $m) {
            if (!is_array($m)) {
                $linhas[] = (string) $m;
                continue;
            }
            $codigo = $m['Codigo'] ?? $m['codigo'] ?? null;
            $descricao = $m['Descricao'] ?? $m['descricao'] ?? null;
            $complemento = $m['Complemento'] ?? $m['complemento'] ?? null;
            $linha = trim(($codigo ? "{$codigo} - " : '') . ($descricao ?? ''));
            if (!empty($complemento)) {
                $linha .= " ({$complemento})";
            }
            $linhas[] = $linha;
        }
        return implode("\n", array_filter($linhas));
    }
}
