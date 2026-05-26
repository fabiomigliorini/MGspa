<?php

namespace Mg\NotaFiscal\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotaFiscalItemTributoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codnotafiscalitemtributo' => $this->codnotafiscalitemtributo,
            'codnotafiscalprodutobarra' => $this->codnotafiscalprodutobarra,
            'codtributo' => $this->codtributo,

            // Tributo
            'tributo' => $this->formatTributo(),

            // CST
            'cst' => $this->cst,

            // Base de Cálculo
            'base' => $this->base,
            'basereducao' => $this->basereducao,
            'basereducaopercentual' => $this->basereducaopercentual,

            // Alíquota e Valores
            'aliquota' => $this->aliquota,
            'valor' => $this->valor,

            // Crédito
            'geracredito' => $this->geracredito,
            'valorcredito' => $this->valorcredito,

            // Benefício Fiscal
            'beneficiocodigo' => $this->beneficiocodigo,
            'fundamentolegal' => $this->fundamentolegal,
            'cclasstrib' => $this->cclasstrib,

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatTributo(): ?array
    {
        return $this->Tributo?->only([
            'codtributo',
            'codigo',
            'descricao',
            'ente'
        ]);
    }
}
