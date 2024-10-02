<?php

namespace Mg\Meta;

use Illuminate\Support\Facades\DB;
use stdClass;

class MetaService
{

    public static function refreshViews ()
    {
        //mwvendasfilial
    }

    public static function vendasFilial(
        Meta $meta
    ) {
        $sql = '
            select v.codfilial, v.filial, v.dia, sum(valorvenda) as valorvenda
            from mwvendas v
            where v.dia between :inicial and :final
            and v.comissaovendedor = 1
            group by v.codfilial, v.filial, v.dia
        ';
        $regs = DB::select($sql, [
            'inicial' => $meta->periodoinicial,
            'final' => $meta->periodofinal
        ]);
        $filiais = collect($regs)->groupBy('codfilial');
        $ret = [];
        foreach ($filiais as $codfilial => $dias) {
            $metaFilial = MetaFilial::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codfilial' => $codfilial,
            ])->toArray();
            $metaFilial['filial'] = $dias[0]->filial;
            $metaFilial['dias'] = $dias->transform(function($dia) {
                return [
                    'dia' => $dia->dia,
                    'valorvenda' => floatval($dia->valorvenda)
                ];
            });
            $metaFilial['valorvenda'] = $dias->sum('valorvenda');
            if (($metaFilial['valormetafilial']??0) > 0) {
                $metaFilial['progresso'] = $dias->sum('valorvenda') / $metaFilial['valormetafilial'];
                $metaFilial['estrelas'] = round($metaFilial['progresso'] * 5, 1);
            } else {
                $metaFilial['progresso'] = null;
                $metaFilial['estrelas'] = null;
            }
            $metaFilial['valorcomissao'] = $metaFilial['valorvenda'] * $meta->percentualcomissaosubgerentemeta * 0.01;
            $ret[] = $metaFilial;
        }
        return $ret;
    }

    public static function vendasVendedor(
        Meta $meta
    ) {
        $sql = '
            select v.codpessoavendedor, v.vendedor, v.dia, sum(valorvenda) as valorvenda
            from mwvendas v
            where v.dia between :inicial and :final
            and v.comissaovendedor = 2
            group by v.codpessoavendedor, v.vendedor, v.dia
        ';
        $regs = DB::select($sql, [
            'inicial' => $meta->periodoinicial,
            'final' => $meta->periodofinal
        ]);
        $vendedores = collect($regs)->groupBy('codpessoavendedor');
        $ret = [];
        foreach ($vendedores as $codpessoavendedor => $dias) {
            $metaVendedor = MetaVendedor::firstOrNew([
                'codmeta' => $meta->codmeta,
                'codpessoa' => $codpessoavendedor,
            ])->toArray();
            $metaVendedor['dias'] = $dias->transform(function($dia) {
                return [
                    'dia' => $dia->dia,
                    'valorvenda' => floatval($dia->valorvenda)
                ];
            });
            $metaVendedor['valorvenda'] = $dias->sum('valorvenda');
            $ret[] = $metaVendedor;
        }
        return $ret;
    }
}
