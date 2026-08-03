<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;

class PessoaCartaoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        // O numero cru (decriptado pelo cast) NUNCA trafega: o que sai sob a
        // chave `numero` e' so' a mascara "1234 **** **** 1234". O model ja'
        // declara o numero cru como $hidden; o unset e' redundancia barata.
        unset($ret['numero']);
        $ret['numero'] = $this->numero_mascarado;

        // `tipo` e `tipo_descricao` saem sozinhos (o segundo vem do $appends).

        // Filial/empresa DO CARTAO (nao do vinculo do titular): dois cartoes da
        // mesma pessoa podem ser de empresas diferentes.
        $ret['filial'] = $this->Filial?->filial;
        $ret['empresa'] = $this->Filial?->Empresa?->empresa;

        return $ret;
    }
}
