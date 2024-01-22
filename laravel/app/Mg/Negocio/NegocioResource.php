<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;
use Mg\PagarMe\PagarMePedidoResource;
use Mg\Pix\PixCobResource;
use Mg\Titulo\TituloResource;

class NegocioResource extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        $ret['percentualdesconto'] = null;
        if ($ret['valordesconto'] > 0 && $ret['valorprodutos'] > 0) {
            $ret['percentualdesconto'] = ($ret['valordesconto'] / $ret['valorprodutos']) * 100;
        }
        $ret['sincronizado'] = true;
        $ret['Pessoa'] = $this->Pessoa->toArray();
        $ret['Pessoa']['formapagamento'] = $this->Pessoa->FormaPagamento->formapagamento??null;
        $ret['Pessoa']['cidade'] = $this->Pessoa->Cidade->cidade??null;
        $ret['Pessoa']['uf'] = $this->Pessoa->Cidade->Estado->sigla??null;
        $ret['naturezaoperacao'] = $this->NaturezaOperacao->naturezaoperacao;
        $ret['financeiro'] = $this->NaturezaOperacao->financeiro;
        $ret['operacao'] = $this->Operacao->operacao;
        $ret['negociostatus'] = $this->NegocioStatus->negociostatus;
        $ret['estoquelocal'] = $this->EstoqueLocal->estoquelocal;
        $ret['fantasia'] = $this->Pessoa->fantasia;
        $ret['fantasiavendedor'] = $this->PessoaVendedor->fantasia ?? null;
        $ret['itens'] = NegocioProdutoBarraResource::collection($this->NegocioProdutoBarraS);
        $ret['pagamentos'] = NegocioFormaPagamentoResource::collection($this->NegocioFormaPagamentoS);
        $ret['pixCob'] = PixCobResource::collection($this->PixCobS()->orderBy('criacao', 'desc')->get());
        $ret['PagarMePedidoS'] = PagarMePedidoResource::collection($this->PagarMePedidoS()->orderBy('criacao', 'desc')->get());
        $ret['titulos'] = collect([]);
        foreach ($this->NegocioFormaPagamentoS()->orderBy('codnegocioformapagamento')->get() as $nfp) {
            $ret['titulos'] = $ret['titulos']->concat(TituloResource::collection($nfp->TituloS()->orderBy('vencimento')->get()));
        }
        return $ret;
    }
}
