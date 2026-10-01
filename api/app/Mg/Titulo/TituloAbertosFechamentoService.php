<?php

namespace Mg\Titulo;

use Carbon\Carbon;

class TituloAbertosFechamentoService
{
    /**
     * Lista títulos abertos para usar no wizard de Liquidação/Agrupamento.
     * Limita a 200 registros para não estourar UI.
     */
    public static function listar(array $filtros): array
    {
        $q = Titulo::query()
            ->select('tbltitulo.*')
            ->with([
                'Pessoa:codpessoa,fantasia',
                'Filial:codfilial,filial',
                'Portador:codportador,portador',
                'NegocioParcela:codnegocioparcela,codnegocio',
                'NegocioParcela.Negocio:codnegocio,confissao',
            ])
            ->join('tblpessoa as p', 'p.codpessoa', '=', 'tbltitulo.codpessoa')
            ->where('tbltitulo.saldo', '<>', 0);

        // Filtros principais
        if (!empty($filtros['codpessoa'])) {
            $q->where('tbltitulo.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codfilial'])) {
            $q->where('tbltitulo.codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['vencimento_de'])) {
            $q->where('tbltitulo.vencimento', '>=', Carbon::parse($filtros['vencimento_de'])->format('Y-m-d'));
        }
        if (!empty($filtros['vencimento_ate'])) {
            $q->where('tbltitulo.vencimento', '<=', Carbon::parse($filtros['vencimento_ate'])->format('Y-m-d'));
        }
        if (!empty($filtros['natureza'])) {
            // R = a receber, P = a pagar (mesmo padrão do TituloListagemService)
            if ($filtros['natureza'] === 'R') {
                $q->where('tbltitulo.valor', '>', 0);
            } elseif ($filtros['natureza'] === 'P') {
                $q->where('tbltitulo.valor', '<', 0);
            }
        }
        if (!empty($filtros['codtipotitulo'])) {
            $q->where('tbltitulo.codtipotitulo', $filtros['codtipotitulo']);
        }
        if (!empty($filtros['codcontacontabil'])) {
            $q->where('tbltitulo.codcontacontabil', $filtros['codcontacontabil']);
        }
        if (!empty($filtros['codportador'])) {
            $q->where('tbltitulo.codportador', $filtros['codportador']);
        }

        $q->orderBy('tbltitulo.vencimento')->orderBy('tbltitulo.saldo')->orderBy('tbltitulo.numero');

        $titulos = $q->limit(200)->get();

        return $titulos->map(function ($t) {
            $saldo = (float)$t->saldo;
            $operacao = $t->ehReceber() ? 'DB' : 'CR';
            return [
                'codtitulo'   => (int)$t->codtitulo,
                'numero'      => $t->numero,
                'fatura'      => $t->fatura,
                'vencimento'  => $t->vencimento,
                'codpessoa'   => (int)$t->codpessoa,
                'fantasia'    => optional($t->Pessoa)->fantasia,
                'codfilial'   => (int)$t->codfilial,
                'filial'      => optional($t->Filial)->filial,
                'codportador' => $t->codportador,
                'portador'    => optional($t->Portador)->portador,
                'gerencial'   => (bool)$t->gerencial,
                'boleto'      => (bool)$t->boleto,
                'nossonumero' => $t->nossonumero,
                'codnegocio'  => optional($t->NegocioParcela)->codnegocio,
                'confissao'   => optional(optional($t->NegocioParcela)->Negocio)->confissao !== null,
                'saldo'       => abs($saldo),
                'operacao'    => $operacao,
            ];
        })->all();
    }
}
