<?php

namespace Mg\Negocio;

use Illuminate\Http\Resources\Json\JsonResource as Resource;


class NegocioListagemResource extends Resource
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
        // $ret['Pessoa'] = $this->Pessoa->toArray();
        $ret['fantasia'] = $this->Pessoa->fantasia;
        $ret['Pessoa']['cidade'] = $this->Pessoa->Cidade->cidade??null;
        $ret['Pessoa']['uf'] = $this->Pessoa->Cidade->Estado->sigla??null;
        $ret['naturezaoperacao'] = $this->NaturezaOperacao->naturezaoperacao;
        $ret['financeiro'] = $this->NaturezaOperacao->financeiro;
        $ret['operacao'] = $this->Operacao->operacao;
        $ret['negociostatus'] = $this->NegocioStatus->negociostatus;
        $ret['estoquelocal'] = $this->EstoqueLocal->estoquelocal;
        $ret['fantasiavendedor'] = $this->PessoaVendedor->fantasia ?? null;
        $ret['usuario'] = $this->Usuario->usuario ?? null;
        $ret['pdv'] = $this->Pdv->apelido ?? null;
        return $ret;
    }

}
