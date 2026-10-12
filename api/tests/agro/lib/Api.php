<?php

namespace AgroBateria;

/** Resposta de uma chamada HTTP. */
final class Resposta
{
    public function __construct(
        public int $status,
        public mixed $json,
        public string $corpo,
        public float $ms,
        public string $erro = '',
    ) {
    }

    public function ok(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    public function erroServidor(): bool
    {
        return $this->status === 0 || $this->status >= 500;
    }

    /**
     * O registro. Resource do Laravel embrulha em { data: {...} } — MENOS a carga,
     * que tem a coluna `data` (chegada) e por isso volta sem embrulho.
     */
    public function dado(): mixed
    {
        $j = $this->json;
        if (is_array($j) && isset($j['data']) && is_array($j['data']) && !isset($j['codcarga'])) {
            return $j['data'];
        }
        return $j;
    }

    public function mensagem(): string
    {
        if (is_array($this->json)) {
            $m = (string) ($this->json['message'] ?? '');
            $erros = $this->json['errors'] ?? null;
            if (is_array($erros) && $erros) {
                $primeiro = reset($erros);
                $txt = is_array($primeiro) ? (string) ($primeiro[0] ?? '') : (string) $primeiro;
                if ($txt !== '' && !str_contains($m, $txt)) {
                    $m = trim($m . ' ' . $txt);
                }
            }
            // 500 do dev (APP_DEBUG) traz o SQL inteiro: fica a primeira linha.
            $m = preg_replace('/\s*\(Connection:.*$/s', '', strtok($m, "\n") ?: '');
            return mb_substr($m, 0, 200);
        }
        return $this->erro !== '' ? $this->erro : mb_substr(trim(strip_tags($this->corpo)), 0, 160);
    }

    public function resumo(): string
    {
        return $this->status . ($this->mensagem() !== '' ? ' ' . $this->mensagem() : '');
    }
}

/**
 * Cliente da API do jeito que o pátio chama: Bearer, Accept JSON (sem ele a API
 * devolve 302 em vez de 401/422) e TLS sem verificação (certificado do dev).
 * Toda chamada entra nas métricas por rótulo (latência e códigos).
 */
final class Api
{
    /** @var array<string, array{ms: float[], codigos: array<int, int>}> */
    public array $metricas = [];

    public function __construct(private string $base, private string $token)
    {
    }

    public function comToken(string $token): self
    {
        return new self($this->base, $token);
    }

    public function get(string $caminho, array $query = [], ?string $rotulo = null): Resposta
    {
        return $this->chamar('GET', $caminho . ($query ? '?' . http_build_query($query) : ''), null, $rotulo);
    }

    public function post(string $caminho, ?array $corpo = null, ?string $rotulo = null): Resposta
    {
        return $this->chamar('POST', $caminho, $corpo ?? [], $rotulo);
    }

    public function put(string $caminho, array $corpo, ?string $rotulo = null): Resposta
    {
        return $this->chamar('PUT', $caminho, $corpo, $rotulo);
    }

    public function delete(string $caminho, ?string $rotulo = null): Resposta
    {
        return $this->chamar('DELETE', $caminho, null, $rotulo);
    }

    public function chamar(string $metodo, string $caminho, ?array $corpo = null, ?string $rotulo = null): Resposta
    {
        $ch = $this->montar($metodo, $caminho, $corpo);
        $corpoResp = curl_exec($ch);
        return $this->fechar($ch, $corpoResp === false ? '' : (string) $corpoResp, $rotulo ?? static::rotulo($metodo, $caminho));
    }

    /**
     * Dispara todos AO MESMO TEMPO (curl_multi) e devolve na ordem dos pedidos.
     * Pedido = [metodo, caminho, corpo|null, rotulo|null].
     *
     * @return Resposta[]
     */
    public function lote(array $pedidos): array
    {
        $mh = curl_multi_init();
        $handles = [];
        foreach ($pedidos as $i => $p) {
            $ch = $this->montar($p[0], $p[1], $p[2] ?? null);
            curl_multi_add_handle($mh, $ch);
            $handles[$i] = $ch;
        }
        do {
            $st = curl_multi_exec($mh, $ativos);
            if ($ativos) {
                curl_multi_select($mh, 1.0);
            }
        } while ($ativos && $st === CURLM_OK);

        $res = [];
        foreach ($handles as $i => $ch) {
            $rot = $pedidos[$i][3] ?? static::rotulo($pedidos[$i][0], $pedidos[$i][1]);
            $res[$i] = $this->fechar($ch, (string) curl_multi_getcontent($ch), $rot);
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
        return $res;
    }

    public function montar(string $metodo, string $caminho, ?array $corpo): \CurlHandle
    {
        $url = str_starts_with($caminho, 'http') ? $caminho : $this->base . ltrim($caminho, '/');
        $ch = curl_init($url);
        $headers = ['Accept: application/json', 'Authorization: Bearer ' . $this->token];
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_CONNECTTIMEOUT => 10,
            // Acima do timeout do nginx (60 s): quem corta é o servidor, e o 504 aparece na métrica.
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CUSTOMREQUEST => $metodo,
        ];
        if ($corpo !== null) {
            $headers[] = 'Content-Type: application/json';
            $opts[CURLOPT_POSTFIELDS] = json_encode($corpo, JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION);
        }
        $opts[CURLOPT_HTTPHEADER] = $headers;
        curl_setopt_array($ch, $opts);
        return $ch;
    }

    public function fechar(\CurlHandle $ch, string $corpo, string $rotulo): Resposta
    {
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $ms = round(((float) curl_getinfo($ch, CURLINFO_TOTAL_TIME)) * 1000, 1);
        $erro = curl_error($ch);
        $json = null;
        if ($corpo !== '') {
            $json = json_decode($corpo, true);
        }
        $this->registrar($rotulo, $ms, $status);
        return new Resposta($status, $json, $corpo, $ms, $erro);
    }

    public function registrar(string $rotulo, float $ms, int $status): void
    {
        $this->metricas[$rotulo]['ms'][] = $ms;
        $this->metricas[$rotulo]['codigos'][$status] = ($this->metricas[$rotulo]['codigos'][$status] ?? 0) + 1;
    }

    public function zerarMetricas(): void
    {
        $this->metricas = [];
    }

    /** "POST v1/carga/{id}/inativo": número vira {id}, query some. */
    public static function rotulo(string $metodo, string $caminho): string
    {
        $c = preg_replace('/\?.*$/', '', $caminho);
        $c = preg_replace('#/\d+(?=/|$)#', '/{id}', $c);
        return $metodo . ' ' . $c;
    }
}

/**
 * Motor assíncrono do estresse: cada aparelho virtual põe o próximo pedido na
 * fila quando o anterior volta, e o curl_multi mantém todos no ar ao mesmo tempo.
 */
final class Motor
{
    private \CurlMultiHandle $mh;
    /** @var array<int, array{0: \CurlHandle, 1: callable, 2: string}> */
    private array $voando = [];

    public function __construct(private Api $api)
    {
        $this->mh = curl_multi_init();
    }

    public function enviar(string $metodo, string $caminho, ?array $corpo, string $rotulo, callable $quandoVoltar): void
    {
        $ch = $this->api->montar($metodo, $caminho, $corpo);
        curl_multi_add_handle($this->mh, $ch);
        $this->voando[spl_object_id($ch)] = [$ch, $quandoVoltar, $rotulo];
    }

    public function voando(): int
    {
        return count($this->voando);
    }

    /** Processa o que terminou (pode enfileirar pedidos novos nos callbacks). */
    public function girar(float $espera = 0.25): void
    {
        curl_multi_exec($this->mh, $ativos);
        if ($this->voando) {
            curl_multi_select($this->mh, $espera);
            curl_multi_exec($this->mh, $ativos);
        }
        while ($info = curl_multi_info_read($this->mh)) {
            $ch = $info['handle'];
            $id = spl_object_id($ch);
            if (!isset($this->voando[$id])) {
                continue;
            }
            [, $quando, $rotulo] = $this->voando[$id];
            unset($this->voando[$id]);
            $resp = $this->api->fechar($ch, (string) curl_multi_getcontent($ch), $rotulo);
            curl_multi_remove_handle($this->mh, $ch);
            $quando($resp);
        }
    }

    public function __destruct()
    {
        curl_multi_close($this->mh);
    }
}
