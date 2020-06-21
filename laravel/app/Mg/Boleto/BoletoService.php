<?php

namespace Mg\Boleto;

use App\Models\Boleto;

use DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Mg\Portador\Portador;

class BoletoService
{
    public static function retornoPendente()
    {
        $portadores = Portador::ativo()->where('emiteboleto', true)->orderBy('codportador')->get();
        $ret = [];
        foreach ($portadores as $portador) {
            $dir = "{$portador->conta}-{$portador->contadigito}/Retorno/";
            $arquivos = Storage::disk('boleto')->files($dir);
            $retornos = [];
            foreach ($arquivos as $arquivo) {
                $info = pathinfo($arquivo);
                switch (strtolower($info['extension'])) {
                    case 'ret':
                        $retornos[] = $info['basename'];
                        break;

                    default:
                        static::arquivarRetornoHtml($portador, $arquivo);
                        // code...
                        break;
                }
            }
            $ret[$portador->codportador] = [
                'codportador' => $portador->codportador,
                'portador' => $portador->portador,
                'retornos' => $retornos
            ];
        }
        return $ret;
    }

    public static function arquivarRetornoHtml($portador, $arquivo)
    {
        $info = pathinfo($arquivo);
        $origem = "{$portador->conta}-{$portador->contadigito}/Retorno/{$info['basename']}";
        $destino = "{$portador->conta}-{$portador->contadigito}/Retorno/html/{$info['basename']}";
        if (Storage::disk('boleto')->exists($destino)) {
            Storage::disk('boleto')->delete($destino);
        }
        return Storage::disk('boleto')->move($origem, $destino);
    }

}
