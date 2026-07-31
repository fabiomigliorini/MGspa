<?php

namespace Mg\Colaborador;

use Illuminate\Http\Resources\Json\JsonResource;

class ColaboradorCartaoResource extends JsonResource
{
    public function toArray($request): array
    {
        $ret = parent::toArray($request);

        // O numero cru (decriptado pelo cast) NUNCA trafega — so' os 4 ultimos
        // (numero_ultimos4, appendado no model).
        unset($ret['numero']);

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
