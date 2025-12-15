<?php

namespace Mg\Woo;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;

use Mg\Estoque\EstoqueLocal;
use Mg\NaturezaOperacao\NaturezaOperacao;
use Mg\Negocio\Negocio;
use Mg\Negocio\NegocioProdutoBarra;

class WooPedidoService
{

    protected WooApi $api;

    public function __construct()
    {
        $this->api = new WooApi();
    }

    // Bucar todos novos pedidos no woocommerce
    public function buscarNovosPedidos()
    {
        $this->api->getOrders(["pending", "processing", "on-hold"]);
        $orders = $this->api->responseObject;
        foreach ($orders as $order) {
            $this->parsePedido($order);
        }
        return true;
    }

    // buscar um pedido especifico no woocommerce
    public function buscarPedido(Int $id)
    {
        $this->api->getOrder($id);
        $this->parsePedido($this->api->responseObject);
        return true;
    }

    // processa o pedido retornado pela API
    public function parsePedido(object $order)
    {
        // busca se ele já existe na tabela local
        $wp = WooPedido::firstOrNew([
            'id' => $order->id,
        ]);

        // preenche os dados do pedido
        $criacaowoo = Carbon::parse(@$order->date_created, 'UTC')->setTimezone(config('app.timezone'));
        $wp->fill([
            'status' => trim(substr(@$order->status, 0, 50)),
            'criacaowoo' => $criacaowoo,
            'valorfrete' => @$order->shipping_total,
            'valortotal' => @$order->total,
            'nome' => trim(substr(@$order->billing->first_name . ' ' . @$order->billing->last_name, 0, 200)),
            'cidade' => trim(substr(@$order->billing->city . '/' . @$order->billing->state, 0, 150)),
            'pagamento' => trim(substr(@$order->payment_method_title, 0, 100)),
            'jsonwoo' => json_encode($order),
        ]);

        // se tiver entrega em local diferente, salva esse dado
        if (isset($order->shipping_lines[0])) {
            $wp->entrega = trim(substr($order->shipping_lines[0]->method_title, 0, 150));
        }

        // salva o pedido
        $wp->save();

        // gera o negocio vinculado ao pedido do woo
        $this->importarNegocio($wp);
        
        return $wp;
    }

    // gera o negocio vinculado ao pedido do woo
    public function importarNegocio(WooPedido $wp)
    {
        // Verifica se existem negocios vinculados ao pedido
        $wpns = WooPedidoNegocio::where([
            'codwoopedido' => $wp->codwoopedido,
        ])->get();

        // Se já houver um Negocio aberto ou fechado, ignora
        foreach ($wpns as $wpn) {
            if (in_array($wpn->Negocio->codnegociostatus, [1, 2])) {
                return true;
            }
        }

        // se nao achou cria    
        if (!isset($wpn)) {
            $wpn = new WooPedidoNegocio([
                'codwoopedido' => $wp->codwoopedido,
            ]);
        }

        // Se não existir, cria um novo Negocio
        if ($wpn->exists) {
            $n = $wpn->Negocio;
        } else {
            $el = EstoqueLocal::findOrFail(env('WOO_CODESTOQUELOCAL'));
            $no = NaturezaOperacao::findOrFail(env('WOO_CODNATUREZAOPERACAO'));
            $n = Negocio::create([
                'codpessoa' => 1, // TODO: Definir cliente correto
                'codnegociostatus' => 1, // Novo
                'lancamento' => $wp->criacaowoo,
                'codfilial' => $el->codfilial,
                'codestoquelocal' => $el->codestoquelocal,
                'codoperacao' => $no->codoperacao, // Saida
                'codnaturezaoperacao' => $no->codnaturezaoperacao, // Venda
                'codusuario' => env('WOO_CODUSUARIO'),
                'codpessoavendedor' => env('WOO_CODPESSOAVENDEDOR'),
                'valorprodutos' => 0,
                'valortotal' => 0,
                'valoraprazo' => 0,
                'valoravista' => 0,
                'uuid' => Str::uuid(),
                'codpdv' => env('WOO_CODPDV'),
            ]);
        }

        // Amarra o Negocio ao Pedido do Woo
        $wpn->codnegocio = $n->codnegocio;
        $wpn->save();

        // calcula a distribuicao do frete entre os itens do pedido
        $order = json_decode($wp->jsonwoo);
        $valorprodutos = $order->total - $order->shipping_total;
        $valorfrete = $order->shipping_total;
        $saldofrete = $valorfrete;
        foreach ($order->line_items as $item) {
            // dd($item);
            $item->valorfrete = round($valorfrete * ($item->total / $valorprodutos), 2);
            $saldofrete -= $item->valorfrete;
            // Ajusta o ultimo item para garantir que o valor do frete some corretamente
            if ($item === end($order->line_items)) {
                $item->valorfrete += $saldofrete;
            }
        }

        // Sincroniza os produtos do Pedido do Woo com o Negocio
        $codnegocioprodutobarras = [];
        $ordenacao = Carbon::now();
        foreach ($order->line_items as $item) {
            $pb = WooProdutoService::descobrirProdutoBarra($item->product_id, $item->variation_id);
            $npb = NegocioProdutoBarra::where([
                'codnegocio' => $n->codnegocio,
                'codprodutobarra' => $pb->codprodutobarra,
            ])->whereNotIn('codnegocioprodutobarra', $codnegocioprodutobarras)->whereNull('inativo')->orderBy('codnegocioprodutobarra')->first();
            if (!$npb) {
                $npb = new NegocioProdutoBarra([
                    'codnegocio' => $n->codnegocio,
                    'codprodutobarra' => $pb->codprodutobarra,
                    'uuid' => Str::uuid(),
                ]);
            }
            $npb->fill([
                'quantidade' => $item->quantity,
                'valorunitario' => $item->price,
                'valorprodutos' => $item->total,
                'valorfrete' => $item->valorfrete,
                'valordesconto' => null,
                'valoroutras' => null,
                'valorseguro' => null,
                'valortotal' => $item->total + $item->valorfrete,
                'ordenacao' => $ordenacao->format('Y-m-d H:i:s'),
            ]);
            $npb->save();
            $ordenacao->addSeconds(-1);
            $codnegocioprodutobarras[] = $npb->codnegocioprodutobarra;
        }

        // inativa os produtos que foram removidos do pedido no Woo
        NegocioProdutoBarra::where('codnegocio', $n->codnegocio)
            ->whereNotIn('codnegocioprodutobarra', $codnegocioprodutobarras)
            ->update(['inativo' => Carbon::now()]);

        // Atualiza os valores totais do Negocio
        $n->fill([
            'valorprodutos' => $valorprodutos,
            'valorfrete' => $valorfrete,
            'valordesconto' => null,
            'valoroutras' => null,
            'valorseguro' => null,
            'valortotal' => $order->total,
            'observacoes' => "WooCommerce ID #{$wp->id}\n\nPagamento: {$wp->pagamento}\n\nEntrega: {$wp->entrega}",
        ]);

        // preenche as observacoes com o endereco completo
        $n->observacoes .= "\n\nEndereço:\n";
        $n->observacoes .= "{$order->shipping->first_name} {$order->shipping->last_name}\n";
        $n->observacoes .= "{$order->shipping->address_1}, {$order->shipping->number} - {$order->shipping->address_2}\n";
        if (!empty($order->shipping->company)) {
            $n->observacoes .= "{$order->shipping->company}\n";
        }
        if (!empty($order->shipping->neighborhood)) {
            $n->observacoes .= "{$order->shipping->neighborhood}\n";
        }
        $n->observacoes .= "{$order->shipping->city}/{$order->shipping->state}\n";
        $n->observacoes .= "CEP {$order->shipping->postcode}\n";
        if (!empty($order->shipping->phone)) {
            $n->observacoes .= "{$order->shipping->phone}\n";
        }
        if (!empty($order->billing->phone)) {
            $n->observacoes .= "{$order->billing->phone}\n";
        }
        if (!empty($order->billing->cellphone)) {
            $n->observacoes .= "{$order->billing->cellphone}\n";
        }

        // salva o negocio
        $n->save();

        // retorna só sucesso
        return true;
    }
}
