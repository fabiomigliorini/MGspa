<?php

namespace Mg\Rh;

use Illuminate\Support\Carbon;
use Mg\Colaborador\Colaborador;
use Mg\Filial\Setor;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioService;
use Mg\Pdv\Pdv;
use Mg\Produto\ProdutoBarra;

class ProcessarVendaService
{
    const TIPO_UNIDADE = 'U';
    const TIPO_SETOR = 'S';
    const TIPO_VENDEDOR = 'V';
    const TIPO_CAIXA = 'C';

    const TIPO_DESCRICAO = [
        self::TIPO_UNIDADE => 'Unidade',
        self::TIPO_SETOR => 'Setor',
        self::TIPO_VENDEDOR => 'Vendedor',
        self::TIPO_CAIXA => 'Caixa',
    ];

    public static function processar(int $codnegocio): void
    {
        $negocio = Negocio::with('NegocioProdutoBarraS')->findOrFail($codnegocio);

        // Filtra por natureza de operação
        $natureza = $negocio->NaturezaOperacao;
        if (!$natureza || (!$natureza->venda && !$natureza->vendadevolucao)) {
            return;
        }

        // Valida status
        if (!in_array($negocio->codnegociostatus, [NegocioService::STATUS_FECHADO, NegocioService::STATUS_CANCELADO])) {
            return;
        }

        $cancelado = ($negocio->codnegociostatus == NegocioService::STATUS_CANCELADO);

        // Sinal base: devolução inverte
        $sinalBase = $natureza->vendadevolucao ? -1 : 1;

        // Resolve PDV e setor do PDV
        $pdv = Pdv::find($negocio->codpdv);
        if (!$pdv || !$pdv->codsetor) {
            return;
        }

        $setorPdv = Setor::find($pdv->codsetor);
        if (!$setorPdv) {
            return;
        }

        $dataVenda = $negocio->lancamento;
        $periodo = static::resolverPeriodo($dataVenda);

        // Resolve colaboradores uma vez
        $codcolaboradorVendedor = $negocio->codpessoavendedor
            ? static::resolverColaboradorPorPessoa($negocio->codpessoavendedor, $dataVenda)
            : null;

        $codcolaboradorCaixa = $negocio->codusuario
            ? static::resolverColaboradorPorUsuario($negocio->codusuario, $dataVenda)
            : null;

        foreach ($negocio->NegocioProdutoBarraS as $item) {

            // Pular item inativo
            if ($item->inativo) {
                continue;
            }

            // Resolve Produto
            $produtoBarra = ProdutoBarra::find($item->codprodutobarra);
            if (!$produtoBarra) {
                continue;
            }

            // Resolve Setor destino
            $setorDestino = $setorPdv;
            if ($produtoBarra->Produto && $produtoBarra->Produto->codtiposetor) {
                $setorPorTipo = Setor::where('codunidadenegocio', $setorPdv->codunidadenegocio)
                    ->where('codtiposetor', $produtoBarra->Produto->codtiposetor)
                    ->first();
                if ($setorPorTipo) {
                    $setorDestino = $setorPorTipo;
                }
            }

            $codunidadenegocio = $setorDestino->codunidadenegocio;

            // Calcula valor base do item (sempre positivo pra venda, negativo pra devolução)
            $valorBase = ($item->valorprodutos - ($item->valordesconto ?? 0)) * $sinalBase;

            // Monta lista de indicadores a acumular
            $indicadores = [];

            // a) Indicador da UNIDADE (sempre)
            $indicadores[] = [self::TIPO_UNIDADE, $codunidadenegocio, null, null];

            // b) Indicador do SETOR (coletivo)
            if ($setorDestino->indicadorcoletivo) {
                $indicadores[] = [self::TIPO_SETOR, $codunidadenegocio, $setorDestino->codsetor, null];
            }

            // c) Indicador do VENDEDOR
            if ($setorDestino->indicadorvendedor && $codcolaboradorVendedor) {
                $indicadores[] = [self::TIPO_VENDEDOR, $codunidadenegocio, $setorDestino->codsetor, $codcolaboradorVendedor];
            }

            // d) Indicador do CAIXA (somente se setor destino = setor do PDV)
            if ($setorDestino->indicadorcaixa && $codcolaboradorCaixa && $setorDestino->codsetor === $setorPdv->codsetor) {
                $indicadores[] = [self::TIPO_CAIXA, $codunidadenegocio, $setorDestino->codsetor, $codcolaboradorCaixa];
            }

            foreach ($indicadores as [$tipo, $codun, $codset, $codcol]) {

                // Lançamento original (venda)
                static::acumularIndicador(
                    $periodo->codperiodo,
                    $tipo,
                    $codun,
                    $codset,
                    $codcol,
                    $valorBase,
                    $item->codnegocioprodutobarra,
                    $codnegocio,
                    false
                );

                // Se cancelado, lança também o estorno (saldo líquido = 0)
                if ($cancelado) {
                    static::acumularIndicador(
                        $periodo->codperiodo,
                        $tipo,
                        $codun,
                        $codset,
                        $codcol,
                        $valorBase * -1,
                        $item->codnegocioprodutobarra,
                        $codnegocio,
                        true
                    );
                }
            }
        }
    }

    protected static function acumularIndicador(
        int $codperiodo,
        string $tipo,
        ?int $codunidadenegocio,
        ?int $codsetor,
        ?int $codcolaborador,
        float $valor,
        int $codnegocioprodutobarra,
        int $codnegocio,
        bool $estorno
    ): void {
        $indicador = static::findOrCreateIndicador($codperiodo, $tipo, $codunidadenegocio, $codsetor, $codcolaborador);

        // Idempotência: chave = (codindicador, codnegocioprodutobarra, estorno)
        $lancamento = IndicadorLancamento::where('codindicador', $indicador->codindicador)
            ->where('codnegocioprodutobarra', $codnegocioprodutobarra)
            ->where('estorno', $estorno)
            ->first();

        if ($lancamento) {
            // Já existe: subtrai valor antigo, atualiza com novo
            $indicador->valoracumulado -= $lancamento->valor;
            $lancamento->valor = $valor;
            $lancamento->save();
        } else {
            IndicadorLancamento::create([
                'codindicador' => $indicador->codindicador,
                'codnegocio' => $codnegocio,
                'codnegocioprodutobarra' => $codnegocioprodutobarra,
                'valor' => $valor,
                'estorno' => $estorno,
                'manual' => false,
            ]);
        }

        $indicador->valoracumulado += $valor;
        $indicador->save();
    }

    protected static function findOrCreateIndicador(
        int $codperiodo,
        string $tipo,
        ?int $codunidadenegocio,
        ?int $codsetor,
        ?int $codcolaborador
    ): Indicador {
        return Indicador::firstOrCreate(
            [
                'codperiodo' => $codperiodo,
                'tipo' => $tipo,
                'codunidadenegocio' => $codunidadenegocio,
                'codsetor' => $codsetor,
                'codcolaborador' => $codcolaborador,
            ],
            [
                'meta' => null,
                'valoracumulado' => 0,
            ]
        );
    }

    protected static function resolverColaboradorPorPessoa(int $codpessoa, Carbon $dataVenda): ?int
    {
        return Colaborador::where('codpessoa', $codpessoa)
            ->where(function ($q) use ($dataVenda) {
                $q->whereNull('rescisao')
                    ->orWhere('rescisao', '>=', $dataVenda->toDateString());
            })
            ->orderBy('codcolaborador')
            ->value('codcolaborador');
    }

    protected static function resolverColaboradorPorUsuario(int $codusuario, Carbon $dataVenda): ?int
    {
        return Colaborador::join('tblusuario', 'tblusuario.codpessoa', '=', 'tblcolaborador.codpessoa')
            ->where('tblusuario.codusuario', $codusuario)
            ->where(function ($q) use ($dataVenda) {
                $q->whereNull('tblcolaborador.rescisao')
                    ->orWhere('tblcolaborador.rescisao', '>=', $dataVenda->toDateString());
            })
            ->orderBy('tblcolaborador.codcolaborador')
            ->value('tblcolaborador.codcolaborador');
    }

    protected static function resolverPeriodo(Carbon $data): Periodo
    {
        // 1. Período que contém a data e está aberto
        $periodo = Periodo::where('periodoinicial', '<=', $data)
            ->where('periodofinal', '>=', $data)
            ->where('status', PeriodoService::STATUS_ABERTO)
            ->first();

        if ($periodo) {
            return $periodo;
        }

        // 2. Próximo período aberto
        $periodo = Periodo::where('periodoinicial', '>', $data)
            ->where('status', PeriodoService::STATUS_ABERTO)
            ->orderBy('periodoinicial')
            ->first();

        if ($periodo) {
            return $periodo;
        }

        // 3. Cria automaticamente baseado no último período
        $ultimo = Periodo::orderBy('periodofinal', 'desc')->first();

        if ($ultimo) {
            return PeriodoService::criarProximoPeriodo($ultimo);
        }

        // Sem nenhum período: cria para o mês da data
        return PeriodoService::criar([
            'periodoinicial' => $data->copy()->startOfMonth()->format('Y-m-d'),
            'periodofinal' => $data->copy()->endOfMonth()->format('Y-m-d'),
            'diasuteis' => 22,
        ]);
    }
}
