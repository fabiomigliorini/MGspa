<?php

namespace Mg\Pdv;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Exception;
use DB;

use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioFormaPagamento;
use Mg\Negocio\NegocioProdutoBarra;
use Mg\Negocio\NegocioStatus;
use Mg\Titulo\BoletoBb\BoletoBbService;

class PdvNegocioService
{

    public static function negocio($data, $pdv)
    {
        // abre transacao no banco
        DB::beginTransaction();

        // Procura se já existe um negocio com o uuid na base
        $negocio = Negocio::firstOrNew(['uuid' => $data['uuid']]);
        if ($negocio->codnegociostatus == 2) {
            throw new \Exception("Tentando atualizar um negocio Fechado {$negocio->codnegocio}!", 1);
        }
        if ($negocio->codnegociostatus == 3) {
            throw new \Exception("Tentando atualizar um negocio Cancelado {$negocio->codnegocio}!", 1);
        }

        // importa os dados do negocio
        $negocio->fill($data);
        $negocio->codpdv = $pdv->codpdv;
        $negocio->codfilial = $negocio->EstoqueLocal->codfilial;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->save();

        // importa os itens
        foreach ($data['itens'] as $item) {
            $npb = NegocioProdutoBarra::firstOrNew(['uuid' => $item['uuid']]);
            if (!empty($npb->codnegocio) && $npb->codnegocio != $negocio->codnegocio) {
                throw new \Exception("Tentando atualizar um item de outro negocio {$npb->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            $npb->fill($item);
            $npb->codnegocio = $negocio->codnegocio;
            $npb->save();
        }

        // importa os pagamentos
        foreach ($data['pagamentos'] as $pagto) {
            // ignora pagamentos criados por integracao de algum sistema
            if ($pagto['integracao']) {
                continue;
            }
            // procura se pagamento já existe
            $nfp = NegocioFormaPagamento::firstOrNew(['uuid' => $pagto['uuid']]);
            if (!empty($nfp->codnegocio) && $nfp->codnegocio != $negocio->codnegocio) {
                throw new \Exception("Tentando atualizar um pagamento de outro negocio {$nfp->codnegocio}/{$negocio->codnegocio}!", 1);
            }
            // vincula pagamento
            $nfp->fill($pagto);
            $nfp->codnegocio = $negocio->codnegocio;
            $nfp->save();
        }

        // exclui pagamentos que nao vieram no post
        $uuids = array_column($data['pagamentos'], 'uuid');
        NegocioFormaPagamento::where('codnegocio', $negocio->codnegocio)->where('integracao', false)->whereNotIn('uuid', $uuids)->delete();

        // salva no banco
        DB::commit();
        return $negocio;
    }

    public static function movimentarEstoque(Negocio $negocio)
    {
        // Chama MGLara para fazer movimentacao do estoque com delay de 10 segundos
        $url = env('MGLARA_URL') . "estoque/gera-movimento-negocio/{$negocio->codnegocio}?delay=10";
        $ret = json_decode(file_get_contents($url, false, stream_context_create([
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ])));
        if (@$ret->response !== 'Agendado') {
            echo '<pre>';
            var_dump($ret);
            echo '<hr>';
            die('Erro ao Gerar Movimentação de Estoque');
            return false;
        }
        return true;
    }

    public static function fechar(Negocio $negocio, Pdv $pdv)
    {
        // inicia transacao no Banco
        DB::beginTransaction();

        // validacao de status
        if ($negocio->codnegociostatus != NegocioStatus::ABERTO) {
            throw new Exception('O Status do Negócio não permite Fechamento!', 1);
        }

        // validacao de itens informados
        if ($negocio->NegocioProdutoBarras()->whereNull('inativo')->count() == 0) {
            throw new Exception('Não foi informado nenhum produto neste negócio!', 1);
        }

        // validacoes de venda
        if ($negocio->NaturezaOperacao->venda == true) {

            // valida se tem CPF/CNPJ
            if (($negocio->valortotal >= 1000) && (empty($negocio->Pessoa->cnpj)) && (empty($negocio->cpf))) {
                throw new Exception('Obrigatório Identificar CPF para vendas acima de R$ 1.000,00!', 1);
            }

            //Calcula total pagamentos à vista e à prazo
            $valorPagamentos = 0;
            $valorPagamentosPrazo = 0;
            foreach ($negocio->NegocioFormaPagamentos as $nfp) {
                $valorPagamentos += $nfp->valorpagamento;
                if (!$nfp->FormaPagamento->avista) {
                    $valorPagamentosPrazo += $nfp->valorpagamento;
                }
            }

            //valida total pagamentos
            if (($negocio->valortotal - $valorPagamentos) >= 0.01) {
                $valorPagamentos = formataNumero($valorPagamentos, 2);
                $valorTotal = formataNumero($negocio->valortotal, 2);
                throw new Exception("O valor dos Pagamentos ($valorPagamentos) é inferior ao Total ($valorTotal)!", 1);
            }

            // valida total à prazo
            if ($valorPagamentosPrazo > $negocio->valortotal) {
                throw new Exception('O valor à Prazo é superior ao Total!', 1);
            }

            // valida se tem limite de credito
            if (!PdvNegocioPrazoService::avaliaLimiteCredito($negocio->Pessoa, $valorPagamentosPrazo)) {
                throw new Exception('Solicite Liberação de Crédito ao Departamento Financeiro!', 1);
            }
        }

        // validacoes de transferencia
        if ($negocio->NaturezaOperacao->transferencia == true) {
            $fil = substr(str_pad($negocio->Filial->Pessoa->cnpj, 14, "0", STR_PAD_LEFT), 0, 8);
            $pes = substr(str_pad($negocio->Pessoa->cnpj, 14, "0", STR_PAD_LEFT), 0, 8);
            if ($fil != $pes) {
                throw new \Exception("A Pessoa destino precisa ser uma Filial!", 1);
            }
        }

        // marca negocio como fechado
        $negocio->codnegociostatus = NegocioStatus::FECHADO;
        $negocio->codusuario = Auth::user()->codusuario;
        $negocio->codpdv = $pdv->codpdv;
        $negocio->lancamento = Carbon::now();
        $negocio->save();

        // gera titulos do financeiro
        PdvNegocioPrazoService::gerarTitulos($negocio);

        // salva transacao no banco de dados
        DB::commit();

        // busca dados atualizados no banco de dados
        $negocio = $negocio->fresh();

        // registra boletos se houver
        try {
            BoletoBbService::registrarPeloNegocio($negocio);
        } catch (\Throwable $th) {
        }

        // agenda movimentacao de estoque
        try {
            static::movimentarEstoque($negocio);
        } catch (\Throwable $th) {
        }

        // retorna
        return $negocio;
    }
}
