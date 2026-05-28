<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoNaturezaOperacaoResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codtributacaonaturezaoperacao' => $this->codtributacaonaturezaoperacao,
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'codtributacao' => $this->codtributacao,
            'tributacao' => $this->Tributacao?->tributacao,
            'codtipoproduto' => $this->codtipoproduto,
            'tipoproduto' => $this->TipoProduto?->tipoproduto,
            'codestado' => $this->codestado,
            'estado' => $this->Estado?->sigla ?? null,
            'ncm' => $this->ncm,
            'bit' => $this->bit,
            'codcfop' => $this->codcfop,
            'cfop' => $this->Cfop?->cfop,
            'cfopDescricao' => $this->Cfop?->descricao,
            'csosn' => $this->csosn,
            'icmsbase' => $this->icmsbase !== null ? (float) $this->icmsbase : null,
            'icmspercentualSimples' => $this->icmspercentual !== null ? (float) $this->icmspercentual : null,
            'icmscst' => $this->icmscst !== null ? (int) $this->icmscst : null,
            'icmslpbase' => $this->icmslpbase !== null ? (float) $this->icmslpbase : null,
            'icmspercentual' => $this->icmspercentual !== null ? (float) $this->icmspercentual : null,
            'piscst' => $this->piscst !== null ? (int) $this->piscst : null,
            'pispercentual' => $this->pispercentual !== null ? (float) $this->pispercentual : null,
            'cofinscst' => $this->cofinscst !== null ? (int) $this->cofinscst : null,
            'cofinspercentual' => $this->cofinspercentual !== null ? (float) $this->cofinspercentual : null,
            'ipicst' => $this->ipicst !== null ? (int) $this->ipicst : null,
            'csllpercentual' => $this->csllpercentual !== null ? (float) $this->csllpercentual : null,
            'irpjpercentual' => $this->irpjpercentual !== null ? (float) $this->irpjpercentual : null,
        ];
    }
}
