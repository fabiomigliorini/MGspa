<?php

namespace Mg\Rh;

use Illuminate\Http\Resources\Json\JsonResource;

class AcertoListResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'codperiodocolaborador' => $this->codperiodocolaborador,
            'codcolaborador'        => $this->codcolaborador,
            'codpessoa'             => $this->codpessoa,
            'nome'                  => $this->nome,
            'status_periodo'        => $this->status_periodo,
            'status_acerto'         => $this->status_acerto,
            'creditos'              => $this->creditos,
            'debitos'               => $this->debitos,
            'financeiro'            => $this->financeiro,
            'folha'                 => $this->folha,
            'remanescente_valor'    => $this->remanescente_valor,
            'remanescente_qtd'      => $this->remanescente_qtd,
            'codunidadenegocio'     => $this->codunidadenegocio,
            'unidade'               => $this->unidade,
        ];
    }
}
