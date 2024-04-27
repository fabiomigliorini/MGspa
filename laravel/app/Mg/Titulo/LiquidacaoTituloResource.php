<?php

namespace Mg\Titulo;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

use Mg\Negocio\NegocioFormaPagamentoService;

class LiquidacaoTituloResource extends Resource
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
        $ret['portador'] = $this->Portador->portador??null;
        $ret['fantasia'] = $this->Pessoa->fantasia;
        $ret['usuario'] = $this->UsuarioCriacao->usuario ?? null;
        $ret['pdv'] = $this->Pdv->apelido ?? null;
        $ret['parceiro'] = $this->PessoaCartao->fantasia??null;
        $ret['nomebandeira'] = NegocioFormaPagamentoService::BANDEIRAS[$ret['bandeira']]?? null;
        $ret['nometipo'] = NegocioFormaPagamentoService::TIPOS[$ret['tipo']]?? null;
        return $ret;
    }

}
