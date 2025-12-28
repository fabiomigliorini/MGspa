<?php

namespace Mg\Tributacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoRegraResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'                    => $this->codtributacaoregra,
            'tributo'               => new TributoResource($this->whenLoaded('Tributo')),
            'codnaturezaoperacao'   => $this->codnaturezaoperacao,
            'ncm'                   => $this->ncm,
            'codestadodestino'      => $this->codestadodestino,
            'codcidadedestino'      => $this->codcidadedestino,
            'basepercentual'        => $this->basepercentual,
            'aliquota'              => $this->aliquota,
            'cst'                   => $this->cst,
            'cclasstrib'            => $this->cclasstrib,
            'geracredito'           => $this->geracredito,
            'beneficiocodigo'       => $this->beneficiocodigo,
            'observacoes'           => $this->observacoes,
            'vigenciainicio'        => $this->vigenciainicio,
            'vigenciafim'           => $this->vigenciafim,
            'criacao'               => $this->criacao,
            'alteracao'             => $this->alteracao,
        ];
    }
}
