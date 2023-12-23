<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

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
        $ret['naturezaoperacao'] = $this->NaturezaOperacao->naturezaoperacao;
        $ret['operacao'] = $this->Operacao->operacao;
        $ret['negociostatus'] = $this->NegocioStatus->negociostatus;
        $ret['estoquelocal'] = $this->EstoqueLocal->estoquelocal;
        $ret['fantasia'] = $this->Pessoa->fantasia;
        $ret['fantasiavendedor'] = ($this->codpessoavendedor)?$this->PessoaVendedor->fantasia:null;
        $ret['itens'] = NegocioProdutoBarraResource::collection($this->NegocioProdutoBarraS);
        return $ret;
    }
}
