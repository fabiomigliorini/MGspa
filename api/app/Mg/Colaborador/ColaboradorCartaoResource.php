<?php

namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;

class ColaboradorCartaoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        // O numero cru (decriptado pelo cast) NUNCA trafega: o que sai sob a
        // chave `numero` e' so' a mascara "1234 **** **** 1234". O model ja'
        // declara o numero cru como $hidden; o unset e' redundancia barata.
        unset($ret['numero']);
        $ret['numero'] = $this->numero_mascarado;

        // Vinculo: empresa/filial a que o cartao pertence + situacao do vinculo.
        $ret['colaborador'] = [
            'codcolaborador' => $this->codcolaborador,
            'filial' => $this->Colaborador?->Filial?->filial,
            'empresa' => $this->Colaborador?->Filial?->Empresa?->empresa,
            'rescisao' => $this->Colaborador?->rescisao,
        ];

        return $ret;
    }
}
