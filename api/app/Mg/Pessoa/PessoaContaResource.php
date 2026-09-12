<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaContaResource extends JsonResource
{
    public function toArray($request)
    {
        $ret = parent::toArray($request);
        // A relação Banco serializa como 'banco' e sobrescreveria a coluna
        // homônima (que guarda o codbanco, usado pelo select do front).
        $ret['banco'] = $this->banco;
        $ret['nomeBanco'] = $this->Banco?->banco;
        // Codigo FEBRABAN do banco (001, 237, 260...), usado em TED/transferencia.
        $ret['numeroBanco'] = $this->Banco?->numerobanco;
        return $ret;
    }
}
