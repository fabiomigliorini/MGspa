<?php

namespace Mg\FormaPagamento;

use Illuminate\Http\Resources\Json\JsonResource as Resource;

class FormaPagamentoResource extends Resource
{
    public function toArray($request)
    {
        return [
            'codformapagamento' => $this->codformapagamento,
            'formapagamento' => $this->formapagamento,
            'formapagamentoecf' => $this->formapagamentoecf,
            'parcelas' => $this->parcelas,
            'diasentreparcelas' => $this->diasentreparcelas,
            'avista' => (bool) $this->avista,
            'boleto' => (bool) $this->boleto,
            'fechamento' => (bool) $this->fechamento,
            'notafiscal' => (bool) $this->notafiscal,
            'entrega' => (bool) $this->entrega,
            'valecompra' => (bool) $this->valecompra,
            'lio' => (bool) $this->lio,
            'pix' => (bool) $this->pix,
            'stone' => (bool) $this->stone,
            'integracao' => (bool) $this->integracao,
            'safrapay' => (bool) $this->safrapay,
            'inativo' => $this->inativo,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
            'codusuariocriacao' => $this->codusuariocriacao,
            'codusuarioalteracao' => $this->codusuarioalteracao,
        ];
    }
}
