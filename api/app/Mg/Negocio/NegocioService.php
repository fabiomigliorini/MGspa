<?php

namespace Mg\Negocio;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;

use Mg\Pagamento\PagamentoService;
use Mg\Pdv\PdvNegocioService;
use Mg\Rh\ProcessarVendaJob;

class NegocioService
{
    const STATUS_ABERTO = 1;
    const STATUS_FECHADO = 2;
    const STATUS_CANCELADO = 3;

    const CODNEGOCIOSTATUS_DESCRICAO = [
        1 => 'Aberto',
        2 => 'Fechado',
        3 => 'Cancelado'
    ];

    public static function fecharSePago (Negocio $negocio)
    {
        static::recalcularTotal($negocio);
        if ($negocio->codnegociostatus != static::STATUS_ABERTO) {
            return false;
        }
        $valorpagamento = static::valorPago($negocio);
        if (($negocio->valortotal - $valorpagamento) > 0.01) {
            return false;
        }
        // se for de um PDV, deixa o usuario fechar pela interface
        if (!empty($negocio->codpdv)) {
            return false;
        }
        return static::fechar($negocio);
    }

    public static function recalcularTotal(Negocio $negocio)
    {
        $negocio->valorjuros = round(
            floatval($negocio->PagamentoS()->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->sum('juros'))
                + floatval($negocio->NegocioParcelaS()->sum('juros')),
            2
        );
        // valorvales e a face dos vales compras do negocio, bruta e simetrica
        // ao valorprodutos (decisao 20 do plano). Sem vale ela vale 0 e a
        // conta e a mesma de sempre -- mas sem ela um negocio com vale que
        // passasse por aqui (unificacao de comanda, por exemplo) perderia a
        // face do vale do total, em silencio.
        $negocio->valortotal = 
            $negocio->valorprodutos 
            + $negocio->valorvales
            - $negocio->valordesconto
            + $negocio->valorfrete
            + $negocio->valorseguro
            + $negocio->valoroutras
            + $negocio->valorjuros
            - \Mg\Pdv\PdvNegocioService::descontoPagamentos($negocio);
        $negocio->save();
    }

    public static function fechar (Negocio $negocio)
    {
        // se for PDV, utiliza rotina de fechamento daquela classe
        if (!empty($negocio->codpdv)) {
            return PdvNegocioService::fechar($negocio, $negocio->Pdv);
        }

        if ($negocio->codnegociostatus != static::STATUS_ABERTO) {
            throw new \Exception("O Status do Negócio não permite Fechamento!", 1);
        }

        if ($negocio->NegocioProdutoBarras()->count() == 0) {
            throw new \Exception("Não foi informado nenhum produto neste negócio!", 1);
        }

        if ($negocio->NaturezaOperacao->venda) {
            if (!\Mg\Pessoa\PessoaService::podeVenderAPrazo($negocio->Pessoa, $negocio->valoraprazo)) {
                throw new \Exception("Solicite Liberação de Crédito ao Departamento Financeiro!", 1);
            }
        }

        //Calcula total pagamentos à vista e à prazo
        $valorPagamentos = static::valorPago($negocio);
        $valorPagamentosPrazo = floatval($negocio->NegocioParcelaS()->sum('valor'));

        //valida total pagamentos
        if (($negocio->valortotal - $valorPagamentos) >= 0.01) {
            throw new \Exception("O valor dos Pagamentos ({$valorPagamentos}) é inferior ao Total ({$negocio->valortotal})!", 1);
        }

        //valida total à prazo
        if ($valorPagamentosPrazo > $negocio->valortotal) {
            throw new \Exception("O valor a prazo ({$valorPagamentosPrazo}) é superior ao Total ({$negocio->valortotal})!", 1);
        }

        //efetiva os pagamentos e gera os títulos das parcelas
        foreach ($negocio->PagamentoS()->where('estado', PagamentoService::ESTADO_PENDENTE)->get() as $pag) {
            PagamentoService::efetivar($pag);
        }
        NegocioParcelaService::gerarTitulos($negocio);

        //atualiza status
        $negocio->update([
            'codnegociostatus' => static::STATUS_FECHADO,
            'codusuario' => Auth::user()->codusuario??$negocio->codusuario,
            'lancamento' => Carbon::now()
        ]);

        // Dispara processamento de indicadores RH
        ProcessarVendaJob::dispatch($negocio->codnegocio);

        return static::movimentaEstoque($negocio);

    }

    public static function movimentaEstoque(Negocio $negocio)
    {
        // Chama MGLara para fazer movimentacao do estoque com delay de 10 segundos
        $url = config('services.mglara.url') . "estoque/gera-movimento-negocio/{$negocio->codnegocio}?delay=10";
        $ret = json_decode(file_get_contents($url));
        if (@$ret->response !== 'Agendado') {
            dd($ret);
            return false;
        }
        return true;
    }

    // Σ total dos pagamentos (contrario subtrai) + Σ parcelas
    public static function valorPago(Negocio $negocio): float
    {
        $pago = 0;
        foreach ($negocio->PagamentoS()->where('estado', '!=', PagamentoService::ESTADO_CANCELADO)->get() as $pag) {
            $pago += $pag->ehSaida() ? -$pag->total : $pag->total;
        }
        return round($pago + floatval($negocio->NegocioParcelaS()->sum('valor')), 2);
    }

}
