<?php

namespace Mg\Pdv;

use Mg\Titulo\LiquidacaoTitulo;

class PdvLiquidacaoService
{
    public static function queryPorLiquidacao($filtros)
    {
        $qry = LiquidacaoTitulo::query();

        // pesquisar: "LIQ",
        foreach ($filtros as $filtro => $valor) {
            if (empty($valor)) {
                continue;
            }
            switch ($filtro) {
                case 'codpdv':
                    $qry->where('codpdv', $valor);
                    break;
                case 'codusuario':
                    $qry->where('codusuario', $valor);
                    break;
                case 'codportador':
                    $qry->where('codportador', $valor);
                    break;
                case 'codliquidacao':
                    $qry->where('codliquidacao', $valor);
                    break;
                case 'transacao_de':
                    $qry->where('transacao', '>=', $valor);
                    break;
                case 'transacao_ate':
                    $qry->where('transacao', '<=', $valor);
                    break;
                case 'codpessoa':
                    $qry->where('codpessoa', $valor);
                    break;
                case 'integracao':
                    $qry->where('codpessoa', $valor);
                    break;
                case 'integracao':
                    // se todos nao precisa fazer nenhum filtro
                    if (sizeof($valor) == 2) {
                        break;
                    }
                    if ($valor[0] == 'Manual') {
                        $qry->where('integracao', false);
                        break;
                    }
                    $qry->where('integracao', true);
                    break;
            }
        }

        $filtros['tipo'] = $filtros['tipo'] ?? '';

        if ($filtros['tipo'] == 'CR') {
            if ($filtros['valor_de'] > 0) {
                $qry->where('credito', '>=', $filtros['valor_de']);
            }
            if ($filtros['valor_ate'] > 0) {
                $qry->where('credito', '<=', $filtros['valor_ate']);
            }
        } elseif ($filtros['tipo'] == 'DB') {
            if ($filtros['valor_de'] > 0) {
                $qry->where('debito', '>=', $filtros['valor_de']);
            }
            if ($filtros['valor_ate'] > 0) {
                $qry->where('debito', '<=', $filtros['valor_ate']);
            }
        }
        $qry->orderBy('transacao', 'desc')->orderBy('criacao', 'desc')->orderBy('codliquidacaotitulo', 'desc');
        return $qry;
    }
}
