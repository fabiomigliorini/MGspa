<?php

namespace Mg\Pdv;

use Mg\Negocio\NegocioService;

/**
 * Filtros da listagem de negocios do PDV, os mesmos na tela e no relatorio
 * em PDF (TASK-189)
 */
class PdvNegocioListagemService
{
    public static function filtrar($qry, array $filtros)
    {
        foreach ($filtros as $filtro => $valor) {
            if (empty($valor)) {
                continue;
            }
            switch ($filtro) {
                case 'valor_de':
                    $qry->where('valortotal', '>=', $valor);
                    break;
                case 'valor_ate':
                    $qry->where('valortotal', '<=', $valor);
                    break;
                case 'lancamento_de':
                    $qry->where('lancamento', '>=', $valor);
                    break;
                case 'lancamento_ate':
                    $qry->where('lancamento', '<=', $valor);
                    break;
                case 'codnegocio':
                    $qry->where('codnegocio', $valor);
                    break;
                case 'codestoquelocal':
                    $qry->where('codestoquelocal', $valor);
                    break;
                case 'codnegociostatus':
                    // R: a venda reaberta, aberta de novo (TASK-30)
                    if ($valor === 'R') {
                        $qry->where('codnegociostatus', NegocioService::STATUS_ABERTO)->whereNotNull('reabertura');
                        break;
                    }
                    $qry->where('codnegociostatus', $valor);
                    break;
                case 'codnaturezaoperacao':
                    $qry->where('codnaturezaoperacao', $valor);
                    break;
                case 'codpessoa':
                    $qry->where('codpessoa', $valor);
                    break;
                case 'codpessoavendedor':
                    $qry->where('codpessoavendedor', $valor);
                    break;
                case 'codpessoatransportador':
                    $qry->where('codpessoatransportador', $valor);
                    break;
                case 'codpdv':
                    $qry->where('codpdv', $valor);
                    break;
                case 'codusuario':
                    $qry->where('codusuario', $valor);
                    break;
                case 'forma':
                    static::filtrarForma($qry, (array) $valor);
                    break;
                case 'integracao':
                    $valor = (array) $valor;
                    if (sizeof($valor) == 2) {
                        // se todos nao precisa fazer nenhum filtro
                        break;
                    }
                    $integrado = ($valor[0] != 'Manual');
                    $qry->whereIn('codnegocio', function ($query) use ($integrado) {
                        PdvNegocioPagamentoService::filtroIntegracao($query, $integrado);
                    });
                    break;
                case 'pdv':
                    break;
                default:
                    break;
            }
        }
    }

    // avista/aprazo como no MGsis (valoravista/valoraprazo > 0), m<meio> do
    // pagamento e c<condicao> da parcela; negocio com qualquer um deles
    private static function filtrarForma($qry, array $formas): void
    {
        $avista = in_array('avista', $formas);
        $aprazo = in_array('aprazo', $formas);
        $especificas = array_values(array_diff($formas, ['avista', 'aprazo']));
        $qry->where(function ($w) use ($avista, $aprazo, $especificas) {
            if ($avista) {
                $w->orWhere('valoravista', '>', 0);
            }
            if ($aprazo) {
                $w->orWhere('valoraprazo', '>', 0);
            }
            if (!empty($especificas)) {
                $w->orWhereIn('codnegocio', function ($query) use ($especificas) {
                    PdvNegocioPagamentoService::filtroForma($query, $especificas);
                });
            }
        });
    }
}
