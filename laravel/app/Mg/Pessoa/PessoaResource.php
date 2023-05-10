<?php

namespace Mg\Pessoa;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class PessoaResource extends JsonResource
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

        // Chave Extrangeira
        $ret['GrupoCliente'] = [
            'codgrupocliente' => $this->GrupoCliente->codgrupocliente,
            'grupocliente' => $this->GrupoCliente->grupocliente,
        ];

        // Filhos
        $ret['PessoaCertidaoS'] = [];
        // foreach ($this->PessoaCertidaoS()->where('validade', '>=', Carbon::now()) as $pc)
        foreach ($this->PessoaCertidaoS as $pc)
        {
            $ret['PessoaCertidaoS'][] = $pc->toArray();
        }


        return $ret;
    }
}
