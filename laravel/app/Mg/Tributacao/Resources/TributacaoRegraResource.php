<?php

namespace Mg\Tributacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoRegraResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codtributacaoregra'    => $this->codtributacaoregra,
            'tributo'               => new TributoResource($this->whenLoaded('Tributo')),
            'codnaturezaoperacao'   => $this->codnaturezaoperacao,
            'naturezaOperacao'      => $this->formatNaturezaOperacao(),
            'codtipoproduto'        => $this->codtipoproduto,
            'tipoProduto'           => $this->formatTipoProduto(),
            'ncm'                   => $this->ncm,
            'codestadodestino'      => $this->codestadodestino,
            'estadoDestino'         => $this->formatEstadoDestino(),
            'codcidadedestino'      => $this->codcidadedestino,
            'cidadeDestino'         => $this->formatCidadeDestino(),
            'tipocliente'           => $this->tipocliente,
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

    private function formatNaturezaOperacao(): ?array
    {
        if (!$this->codnaturezaoperacao || !$this->relationLoaded('NaturezaOperacao')) {
            return null;
        }

        return $this->NaturezaOperacao->only(['codnaturezaoperacao', 'naturezaoperacao']);
    }

    private function formatEstadoDestino(): ?array
    {
        if (!$this->codestadodestino || !$this->relationLoaded('EstadoDestino')) {
            return null;
        }

        return $this->EstadoDestino->only(['codestado', 'estado', 'sigla']);
    }

    private function formatCidadeDestino(): ?array
    {
        if (!$this->codcidadedestino || !$this->relationLoaded('CidadeDestino')) {
            return null;
        }

        $cidade = $this->CidadeDestino->only(['codcidade', 'cidade']);

        if ($this->CidadeDestino->relationLoaded('Estado')) {
            $cidade['uf'] = $this->CidadeDestino->Estado->sigla;
        }

        return $cidade;
    }

    private function formatTipoProduto(): ?array
    {
        if (!$this->codtipoproduto || !$this->relationLoaded('TipoProduto')) {
            return null;
        }

        return $this->TipoProduto->only(['codtipoproduto', 'tipoproduto']);
    }
}
