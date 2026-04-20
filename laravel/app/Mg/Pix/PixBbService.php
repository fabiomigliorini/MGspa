<?php

namespace Mg\Pix;

use Illuminate\Support\Facades\Log;

use Carbon\Carbon;

use Mg\Titulo\BoletoBb\BoletoBbService;
use Mg\Portador\Portador;
use Mg\Filial\CertificadoService;

class PixBbService
{

    public static function transmitirPixCob(PixCob $pixCob)
    {
        $filial = CertificadoService::filialDoPortador($pixCob->Portador);
        $bbtoken = BoletoBbService::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixBbApiService::transmitirPixCob(
            $filial,
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
        if (!empty($dadosPix['detail'])) {
            throw new \Exception('API do BB retornou: ' . $dadosPix['detail']);
        }
        Log::info('PixBbService::transmitirPixCob resposta', ['dadosPix' => $dadosPix]);
        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);
        $ret = $pixCob->update([
            'location' => $dadosPix['location'] ?? $dadosPix['loc']['location'] ?? null,
            'qrcode' => $dadosPix['pixCopiaECola'] ?? null,
            'txid' => $dadosPix['txid'],
            'codpixcobstatus' => $status->codpixcobstatus
        ]);
        return $pixCob;
    }

    public static function consultarPixCob(PixCob $pixCob)
    {
        $filial = CertificadoService::filialDoPortador($pixCob->Portador);
        $bbtoken = BoletoBbService::verificaTokenValido($pixCob->Portador);
        $dadosPix = PixBbApiService::consultarPixCob(
            $filial,
            $bbtoken,
            $pixCob->Portador->bbdevappkey,
            $pixCob->txid
        );

        if (isset($dadosPix['erros'])) {
            throw new \Exception($dadosPix['erros'][0]['mensagem']);
        }
        if (!empty($dadosPix['detail'])) {
            throw new \Exception('API do BB retornou: ' . $dadosPix['detail']);
        }

        $status = PixCobStatus::firstOrCreate([
            'pixcobstatus' => $dadosPix['status']
        ]);

        $ret = $pixCob->update([
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
    ) {
        $filial = CertificadoService::filialDoPortador($portador);
        $bbtoken = BoletoBbService::verificaTokenValido($portador);

        $strInicio = null;
        if (!empty($inicio)) {
            $strInicio = $inicio->toIso8601String();
        }
        $strFim = null;
        if (!empty($fim)) {
            $strFim = $fim->toIso8601String();
        }
        $ret = PixBbApiService::consultarPix(
            $filial,
            $bbtoken,
            $portador->bbdevappkey,
            $strInicio,
            $strFim,
            $pagina
        );

        if (isset($ret['erros'][0])) {
            throw new \Exception($ret['erros'][0]['mensagem'], 1);
        }
        if (!empty($ret['detail'])) {
            if (stripos($ret['detail'], 'Nenhum resultado encontrado') !== false) {
                return [
                    'pix' => [],
                    'parametros' => [],
                    'processados' => collect([]),
                ];
            }
            throw new \Exception('API do BB retornou: ' . $ret['detail'], 1);
        }

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
