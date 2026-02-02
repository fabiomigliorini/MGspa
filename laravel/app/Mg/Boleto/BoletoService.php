<?php

namespace Mg\Boleto;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

use Mg\Portador\Portador;
use Mg\Sequence\SequenceService;
use Mg\Titulo\Titulo;

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

    public static function retornoFalha()
    {
        $regs = BoletoRetorno::whereNull('codtitulo')
            ->orderBy('codportador')
            ->orderBy('dataretorno')
            ->orderBy('arquivo')
            ->orderBy('linha')
            ->get();
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
                'arquivo',
                'codportador',
            ]);
            $tmp['dataretorno'] = $reg->dataretorno->toW3cString();
            $tmp['motivo'] = $reg->BoletoMotivoOcorrencia->motivo;
            $tmp['ocorrencia'] = $reg->BoletoMotivoOcorrencia->BoletoTipoOcorrencia->ocorrencia;
            $tmp['fantasia'] = null;
            $ret[] = $tmp;
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

    public static function retorno($codportador, $arquivo, $dataretorno)
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

    public static function remessaPendente()
    {
        // $portadores = Portador::ativo()->where('emiteboleto', true)->orderBy('codportador')->get();
        $portadores = Portador::ativo()->where('emiteboleto', true)->orderBy('codportador')->get();
        $ret = [];
        foreach ($portadores as $portador) {
            $titulos = $portador->TituloS()
                ->with('Pessoa')
                ->with('Filial')
                ->where('saldo', '>', 0)
                ->where('boleto', true)
                ->whereNull('remessa')
                ->orderBy('codtitulo')
                ->get();
            $retTitulos = [];
            foreach ($titulos as $titulo) {
                // $retTitulos[] = $titulo;
                $retTitulos[] = [
                    'codtitulo' => $titulo->codtitulo,
                    'codfilial' => $titulo->codfilial,
                    'filial' => $titulo->Filial->filial,
                    'codpessoa' => $titulo->codpessoa,
                    'pessoa' => $titulo->Pessoa->pessoa,
                    'fantasia' => $titulo->Pessoa->fantasia,
                    'numero' => $titulo->numero,
                    'nossonumero' => $titulo->nossonumero,
                    'fatura' => $titulo->fatura,
                    'emissao' => $titulo->emissao->toW3cString(),
                    'vencimento' => $titulo->vencimento->toW3cString(),
                    'debito' => $titulo->debito,
                    'saldo' => $titulo->saldo,
                    'remessa' => $titulo->remessa,
                ];
            }
            $dir = "{$portador->conta}-{$portador->contadigito}/Remessa/";
            $arquivos = Storage::disk('boleto')->files($dir);
            $remessas = [];
            foreach ($arquivos as $arquivo) {
                $info = pathinfo($arquivo);
                if (strtolower($info['extension']) == 'rem') {
                    $remessas[] = $info['basename'];
                }
            }
            if ($titulos->count() == 0 && empty($remessas)) {
                continue;
            }
            $ret[] = [
                'codportador' => $portador->codportador,
                'portador' => $portador->portador,
                'proximaremessa' => SequenceService::simulaProximo("tbltitulo_remessa_{$portador->codportador}_seq"),
                'titulos' => $retTitulos,
                'remessas' => $remessas
            ];
        }
        return $ret;
    }

    public static function remessaEnviada()
    {
        $portadores = Portador::ativo()->where('emiteboleto', true)->orderBy('portador')->get();
        $ret = [];
        foreach ($portadores as $portador) {
            $sql = "
                select
                    codportador,
                    remessa,
                    sum(debito) as total,
                    sum(saldo) as saldo,
                    count(codtitulo) as quantidade
                from tbltitulo
                where boleto = true
                and codportador = :codportador
                and remessa is not null
                group by codportador, remessa
                order by remessa desc
            ";
            $arquivos =
                $ret[] = [
                    'codportador' => $portador->codportador,
                    'portador' => $portador->portador,
                    'remessas' => DB::select($sql, ['codportador' => $portador->codportador])
                ];
        }
        return $ret;
    }

    public static function remessa($codportador, $remessa)
    {
        $regs = Titulo::where([
            'codportador' => $codportador,
            'boleto' => true,
            'remessa' => $remessa,
        ])->with('Pessoa')->with('Filial')->orderBy('numero')->get();

        $ret = [];
        foreach ($regs as $reg) {
            $tmp = $reg->only([
                "codtitulo",
                "numero",
                "debito",
                "nossonumero",
                "saldo",
                "codfilial",
                "codpessoa",
            ]);
            $tmp['emissao'] = $reg->emissao->toW3cString();
            $tmp['vencimento'] = $reg->vencimento->toW3cString();
            $tmp['filial'] = $reg->Filial->filial;
            $tmp['fantasia'] = $reg->Pessoa->fantasia;
            $ret[] = $tmp;
        }
        return $ret;
    }
}
