<?php

namespace Mg\Titulo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TituloListagemService
{
    public static function listar(array $filtros)
    {
        return self::query($filtros, [
            'Pessoa:codpessoa,fantasia,pessoa,codgrupoeconomico,codgrupocliente',
            'Filial:codfilial,filial',
            'Portador:codportador,portador,codbanco,codfilial',
            'TipoTitulo:codtipotitulo,tipotitulo,credito,debito,pagar,receber',
            'ContaContabil:codcontacontabil,contacontabil',
            'UsuarioCriacao:codusuario,usuario',
            'NegocioFormaPagamento:codnegocioformapagamento,codnegocio',
        ])->paginate(50);
    }

    /**
     * Monta o query builder aplicando todos os filtros e ordenação, mas sem paginar.
     * Útil para relatórios.
     */
    public static function query(array $filtros, array $with = [])
    {
        $q = Titulo::query()->select('tbltitulo.*');

        if (!empty($with)) {
            $q->with($with);
        }

        $q->join('tblpessoa as p', 'p.codpessoa', '=', 'tbltitulo.codpessoa');

        self::aplicarFiltrosBasicos($q, $filtros);
        self::aplicarFiltrosTexto($q, $filtros);
        self::aplicarFiltrosData($q, $filtros);
        self::aplicarFiltrosValor($q, $filtros);
        self::aplicarFiltrosStatus($q, $filtros);
        self::aplicarFiltrosBoleto($q, $filtros);
        self::aplicarFiltrosPagarReceber($q, $filtros);
        self::aplicarFiltroPortador($q, $filtros);
        self::aplicarFiltroGrupoCliente($q, $filtros);
        self::aplicarOrdenacao($q, $filtros);

        return $q;
    }

    private static function aplicarFiltrosBasicos($q, array $filtros): void
    {
        if (!empty($filtros['codtitulo'])) {
            $q->where('tbltitulo.codtitulo', preg_replace('/[^0-9]/', '', (string)$filtros['codtitulo']));
        }
        if (!empty($filtros['codfilial'])) {
            $q->where('tbltitulo.codfilial', $filtros['codfilial']);
        }
        if (!empty($filtros['codpessoa'])) {
            $q->where('tbltitulo.codpessoa', $filtros['codpessoa']);
        }
        if (!empty($filtros['codgrupoeconomico'])) {
            $q->where('p.codgrupoeconomico', $filtros['codgrupoeconomico']);
        }
        if (!empty($filtros['codtipotitulo'])) {
            $q->where('tbltitulo.codtipotitulo', $filtros['codtipotitulo']);
        }
        if (!empty($filtros['codusuariocriacao'])) {
            $q->where('tbltitulo.codusuariocriacao', $filtros['codusuariocriacao']);
        }

        if (!empty($filtros['codcontacontabil'])) {
            $valores = is_array($filtros['codcontacontabil']) ? $filtros['codcontacontabil'] : [$filtros['codcontacontabil']];
            $q->whereIn('tbltitulo.codcontacontabil', $valores);
        }

        // gerencial (1 = gerencial; 2 = fiscal)
        if (!empty($filtros['gerencial'])) {
            if ((int)$filtros['gerencial'] === 1) {
                $q->where('tbltitulo.gerencial', true);
            } elseif ((int)$filtros['gerencial'] === 2) {
                $q->where('tbltitulo.gerencial', false);
            }
        }

        // credito (1 = credito; 2 = debito)
        if (!empty($filtros['credito'])) {
            if ((int)$filtros['credito'] === 1) {
                $q->where('tbltitulo.credito', '>', 0);
            } elseif ((int)$filtros['credito'] === 2) {
                $q->where('tbltitulo.debito', '>', 0);
            }
        }
    }

    private static function aplicarFiltrosTexto($q, array $filtros): void
    {
        if (!empty($filtros['numero'])) {
            $texto = '%' . str_replace(' ', '%', trim($filtros['numero'])) . '%';
            $q->where('tbltitulo.numero', 'ilike', $texto);
        }
        if (!empty($filtros['nossonumero'])) {
            $texto = '%' . str_replace(' ', '%', trim($filtros['nossonumero'])) . '%';
            $q->where('tbltitulo.nossonumero', 'ilike', $texto);
        }
    }

    private static function aplicarFiltrosData($q, array $filtros): void
    {
        $mapDe = [
            'vencimento_de' => 'tbltitulo.vencimento',
            'emissao_de' => 'tbltitulo.emissao',
            'criacao_de' => 'tbltitulo.criacao',
            'liquidacao_de' => 'tbltitulo.transacaoliquidacao',
        ];
        foreach ($mapDe as $key => $coluna) {
            if (!empty($filtros[$key])) {
                $q->where($coluna, '>=', self::dataInicio($filtros[$key]));
            }
        }

        $mapAte = [
            'vencimento_ate' => 'tbltitulo.vencimento',
            'emissao_ate' => 'tbltitulo.emissao',
            'criacao_ate' => 'tbltitulo.criacao',
            'liquidacao_ate' => 'tbltitulo.transacaoliquidacao',
        ];
        foreach ($mapAte as $key => $coluna) {
            if (!empty($filtros[$key])) {
                $q->where($coluna, '<=', self::dataFim($filtros[$key]));
            }
        }
    }

    private static function aplicarFiltrosValor($q, array $filtros): void
    {
        $map = [
            'debito_de' => ['tbltitulo.debito', '>='],
            'debito_ate' => ['tbltitulo.debito', '<='],
            'credito_de' => ['tbltitulo.credito', '>='],
            'credito_ate' => ['tbltitulo.credito', '<='],
        ];
        foreach ($map as $key => [$coluna, $op]) {
            if (isset($filtros[$key]) && $filtros[$key] !== '' && $filtros[$key] !== null) {
                $q->where($coluna, $op, (float)($filtros[$key]));
            }
        }
        if (isset($filtros['saldo_de']) && $filtros['saldo_de'] !== '' && $filtros['saldo_de'] !== null) {
            $q->whereRaw('abs(tbltitulo.saldo) >= ?', [(float)($filtros['saldo_de'])]);
        }
        if (isset($filtros['saldo_ate']) && $filtros['saldo_ate'] !== '' && $filtros['saldo_ate'] !== null) {
            $q->whereRaw('abs(tbltitulo.saldo) <= ?', [(float)($filtros['saldo_ate'])]);
        }
    }

    private static function aplicarFiltrosStatus($q, array $filtros): void
    {
        // status (saldo) - default Abertos
        $status = $filtros['status'] ?? 'A';
        switch ($status) {
            case 'A': // Abertos
                $q->where('tbltitulo.saldo', '<>', 0);
                break;
            case 'L': // Liquidados (saldo = 0 e nao estornado)
                $q->where('tbltitulo.saldo', 0)
                  ->whereNull('tbltitulo.estornado');
                break;
            case 'AL': // Abertos e liquidados
                $q->whereNull('tbltitulo.estornado');
                break;
            case 'E': // Estornados
                $q->whereNotNull('tbltitulo.estornado');
                break;
            case 'LE': // Liquidados e Estornados (saldo = 0)
                $q->where('tbltitulo.saldo', 0);
                break;
            case 'T': // Todos - sem filtro
                break;
        }
    }

    private static function aplicarFiltrosBoleto($q, array $filtros): void
    {
        if (empty($filtros['boleto'])) return;

        switch ($filtros['boleto']) {
            case 'B':
                $q->where('tbltitulo.boleto', true);
                break;
            case 'SB':
                $q->where('tbltitulo.boleto', false);
                break;
            case 'BA': // boleto aberto no banco
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbltituloboleto as tb')
                        ->whereColumn('tb.codtitulo', 'tbltitulo.codtitulo')
                        ->whereNotIn('tb.estadotitulocobranca', [6, 7]);
                });
                break;
            case 'BL': // boleto liquidado no banco
                $q->whereExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbltituloboleto as tb')
                        ->whereColumn('tb.codtitulo', 'tbltitulo.codtitulo')
                        ->whereIn('tb.estadotitulocobranca', [6, 7]);
                });
                $q->whereNotExists(function ($sub) {
                    $sub->select(DB::raw(1))
                        ->from('tbltituloboleto as tb')
                        ->whereColumn('tb.codtitulo', 'tbltitulo.codtitulo')
                        ->whereNotIn('tb.estadotitulocobranca', [6, 7]);
                });
                break;
        }
    }

    private static function aplicarFiltrosPagarReceber($q, array $filtros): void
    {
        if (empty($filtros['pagarreceber'])) return;

        if ($filtros['pagarreceber'] === 'R') {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tbltipotitulo')
                    ->whereColumn('tbltipotitulo.codtipotitulo', 'tbltitulo.codtipotitulo')
                    ->where('tbltipotitulo.receber', true);
            });
        } elseif ($filtros['pagarreceber'] === 'P') {
            $q->whereExists(function ($sub) {
                $sub->select(DB::raw(1))
                    ->from('tbltipotitulo')
                    ->whereColumn('tbltipotitulo.codtipotitulo', 'tbltitulo.codtipotitulo')
                    ->where('tbltipotitulo.pagar', true);
            });
        }
    }

    private static function aplicarFiltroPortador($q, array $filtros): void
    {
        if (empty($filtros['codportador'])) return;

        $valores = is_array($filtros['codportador']) ? $filtros['codportador'] : [$filtros['codportador']];
        $q->where(function ($w) use ($valores) {
            $semPortador = false;
            $cods = [];
            foreach ($valores as $v) {
                if ((int)$v === -1) $semPortador = true;
                else $cods[] = $v;
            }
            if (!empty($cods)) $w->whereIn('tbltitulo.codportador', $cods);
            if ($semPortador) $w->orWhereNull('tbltitulo.codportador');
        });
    }

    private static function aplicarFiltroGrupoCliente($q, array $filtros): void
    {
        if (empty($filtros['codgrupocliente'])) return;

        $valores = is_array($filtros['codgrupocliente']) ? $filtros['codgrupocliente'] : [$filtros['codgrupocliente']];
        $q->where(function ($w) use ($valores) {
            $semGrupo = false;
            $cods = [];
            foreach ($valores as $v) {
                if ((int)$v === -1) $semGrupo = true;
                else $cods[] = $v;
            }
            if (!empty($cods)) $w->whereIn('p.codgrupocliente', $cods);
            if ($semGrupo) $w->orWhereNull('p.codgrupocliente');
        });
    }

    private static function aplicarOrdenacao($q, array $filtros): void
    {
        $ordem = $filtros['ordem'] ?? 'AV';
        switch ($ordem) {
            case 'AE':
                $q->orderBy('p.fantasia')->orderBy('tbltitulo.emissao')->orderBy('tbltitulo.fatura')->orderBy('tbltitulo.numero')->orderBy('tbltitulo.saldo');
                break;
            case 'CV':
                $q->orderBy('p.codpessoa')->orderBy('tbltitulo.vencimento')->orderBy('tbltitulo.saldo');
                break;
            case 'CE':
                $q->orderBy('p.codpessoa')->orderBy('tbltitulo.emissao')->orderBy('tbltitulo.fatura')->orderBy('tbltitulo.numero')->orderBy('tbltitulo.saldo');
                break;
            case 'VS':
                $q->orderBy('tbltitulo.vencimento')->orderBy('tbltitulo.saldo')->orderBy('tbltitulo.numero')->orderBy('p.codpessoa');
                break;
            case 'AV':
            default:
                $q->orderBy('p.fantasia')->orderBy('tbltitulo.vencimento')->orderBy('tbltitulo.saldo');
                break;
        }
    }

    private static function dataInicio($valor): string
    {
        return Carbon::parse($valor)->startOfDay()->format('Y-m-d H:i:s');
    }

    private static function dataFim($valor): string
    {
        return Carbon::parse($valor)->endOfDay()->format('Y-m-d H:i:s');
    }
}
