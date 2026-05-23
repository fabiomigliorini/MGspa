<?php

namespace Mg\Pix\Sicredi;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

use Mg\Pix\PixCob;
use Mg\Pix\PixCobStatus;
use Mg\Pix\PixService;
use Mg\Portador\Portador;

class PixSicrediService
{

    public static function verificaTokenValido(Portador $portador): string
    {
        $cacheKey = "sicredi_token_{$portador->codportador}";
        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }
        $token = PixSicrediApiService::token($portador);
        $ttl = intval($token['expires_in'] * 0.5);
        Cache::put($cacheKey, $token['access_token'], $ttl);
        return $token['access_token'];
    }

    private static function verificarErro(array $dadosPix): void
    {
        // Padrão BACEN: { "type": "...", "title": "...", "status": 400, "detail": "..." }
        if (!empty($dadosPix['detail'])) {
            $msg = 'API do Sicredi retornou: ' . $dadosPix['detail'];
            if (!empty($dadosPix['violacoes'])) {
                foreach ($dadosPix['violacoes'] as $v) {
                    $msg .= " | {$v['propriedade']}: {$v['razao']}";
                }
            }
            throw new \Exception($msg);
        }
        if (!empty($dadosPix['title']) && !empty($dadosPix['status']) && $dadosPix['status'] >= 400) {
            throw new \Exception('API do Sicredi retornou: ' . $dadosPix['title']);
        }
    }

    public static function transmitirPixCob(PixCob $pixCob): PixCob
    {
        $token = static::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixSicrediApiService::transmitirPixCob(
            $token,
            $pixCob->Portador->pixdict,
            $pixCob->txid,
            $pixCob->expiracao,
            $pixCob->valororiginal,
            $pixCob->solicitacaopagador,
            $pixCob->nome,
            $pixCob->cpf,
            $pixCob->cnpj
        );
        Log::info('PixSicrediService::transmitirPixCob resposta', ['dadosPix' => $dadosPix]);
        static::verificarErro($dadosPix);

        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);
        $pixCob->update([
            'location' => $dadosPix['location'] ?? $dadosPix['loc']['location'] ?? null,
            'qrcode' => $dadosPix['pixCopiaECola'] ?? null,
            'txid' => $dadosPix['txid'],
            'codpixcobstatus' => $status->codpixcobstatus
        ]);
        return $pixCob;
    }

    public static function consultarPixCob(PixCob $pixCob): PixCob
    {
        $token = static::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixSicrediApiService::consultarPixCob(
            $token,
            $pixCob->txid
        );
        static::verificarErro($dadosPix);

        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);
        $pixCob->update([
            'location' => $dadosPix['location'] ?? $dadosPix['loc']['location'] ?? $pixCob->location,
            'qrcode' => $dadosPix['pixCopiaECola'] ?? $pixCob->qrcode,
            'codpixcobstatus' => $status->codpixcobstatus
        ]);

        if (isset($dadosPix['pix'])) {
            foreach ($dadosPix['pix'] as $pix) {
                PixService::importarPix($pixCob->Portador, $pix, $pixCob);
            }
        }

        $pixCob = $pixCob->fresh();
        return $pixCob;
    }

    public static function consultarPix(
        Portador $portador,
        Carbon $inicio = null,
        Carbon $fim = null,
        int $pagina = 0
    ): array {
        $token = static::verificaTokenValido($portador);

        $strInicio = null;
        if (!empty($inicio)) {
            $strInicio = $inicio->toIso8601String();
        }
        $strFim = null;
        if (!empty($fim)) {
            $strFim = $fim->toIso8601String();
        }
        $ret = PixSicrediApiService::consultarPix(
            $token,
            $strInicio,
            $strFim,
            $pagina
        );
        static::verificarErro($ret);

        $processados = collect([]);
        if (isset($ret['pix'])) {
            foreach ($ret['pix'] as $item) {
                $processados[] = PixService::importarPix($portador, $item);
            }
        }

        $ret['processados'] = $processados;
        return $ret;
    }

}
