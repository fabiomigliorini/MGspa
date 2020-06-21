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
            if (empty($retornos)) {
                continue;
            }
            $ret[] = [
                'codportador' => $portador->codportador,
                'portador' => $portador->portador,
                'retornos' => $retornos
            ];
        }
        return $ret;
    }

    public static function retornoProcessado()
    {
        $sql = "
            select
            	br.dataretorno,
            	br.arquivo,
            	br.codportador,
            	p.portador,
            	count(br.codportador) as registros,
            	count(br.codtitulo) as sucesso,
                count(br.codportador) - count(br.codtitulo) as falha,
            	sum(br.pagamento) as pagamento,
            	sum(br.valor) as valor,
            	null
            from tblboletoretorno br
            inner join tblportador p on (p.codportador = br.codportador)
            where
                br.dataretorno >= date_trunc('year', now() - '1 year'::interval)
            group by
            	br.codportador, p.portador, br.dataretorno, br.arquivo
            order by
            	dataretorno DESC, arquivo DESC, codportador
            ";
        $arquivos = DB::select($sql);
        return $arquivos;
    }

    public static function retorno ($codportador, $arquivo, $dataretorno)
    {
        $regs = BoletoRetorno::where([
            'codportador' => $codportador,
            'arquivo' => $arquivo,
            'dataretorno' => $dataretorno,
        ])->orderBy('linha')->get();

        $ret = [];
        foreach ($regs as $reg) {
            $tmp = $reg->only([
                'codboletoretorno',
                'linha',
                'nossonumero',
                'numero',
                'valor',
                'codbancocobrador',
                'agenciacobradora',
                'agenciacobradora',
                'codboletomotivoocorrencia',
                'despesas',
                'outrasdespesas',
                'jurosatraso',
                'abatimento',
                'desconto',
                'pagamento',
                'jurosmora',
                'protesto',
                'codtitulo',
            ]);
            $tmp['motivo'] = $reg->BoletoMotivoOcorrencia->motivo;
            $tmp['ocorrencia'] = $reg->BoletoMotivoOcorrencia->BoletoTipoOcorrencia->ocorrencia;
            $tmp['fantasia'] = null;
            if (!empty($reg->codtitulo)) {
                $tmp['fantasia'] = $reg->Titulo->Pessoa->fantasia;
            }
            $ret[] = $tmp;
            // $ret[] = [
            //
            // ];
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
