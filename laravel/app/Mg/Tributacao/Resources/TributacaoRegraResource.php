<?php

namespace Mg\Tributacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoRegraResource extends JsonResource
{
    public function toArray($request): array
    {
        $cidade = null;
        if ($this->codcidadedestino) {
            $cidade = $this->CidadeDestino->only(['codcidade', 'cidade']);
            $cidade['uf'] = $this->CidadeDestino->Estado->sigla;
        }
        $estado = null;
        if ($this->estadodestino) {
            $estado = $this->EstadoDestino->only(['codestado', 'estado', 'sigla']);
        }
        return [
            'codtributacaoregra'    => $this->codtributacaoregra,
            'tributo'               => new TributoResource($this->whenLoaded('Tributo')),
            'codnaturezaoperacao'   => $this->codnaturezaoperacao,
            'ncm'                   => $this->ncm,
            'codestadodestino'      => $this->codestadodestino,
            'EstadoDestino'         => $estado,
            'codcidadedestino'      => $this->codcidadedestino,
            'CidadeDestino'         => $cidade,
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
