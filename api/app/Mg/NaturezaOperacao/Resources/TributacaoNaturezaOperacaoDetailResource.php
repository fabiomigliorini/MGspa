<?php

namespace Mg\NaturezaOperacao\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TributacaoNaturezaOperacaoDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'codtributacaonaturezaoperacao' => $this->codtributacaonaturezaoperacao,
            'codnaturezaoperacao' => $this->codnaturezaoperacao,
            'codtributacao' => $this->codtributacao,
            'tributacao' => $this->Tributacao?->only(['codtributacao', 'tributacao']),
            'codtipoproduto' => $this->codtipoproduto,
            'tipoProduto' => $this->TipoProduto?->only(['codtipoproduto', 'tipoproduto']),
            'codestado' => $this->codestado,
            'estado' => $this->Estado?->only(['codestado', 'sigla', 'estado']),
            'ncm' => $this->ncm,
            'bit' => $this->bit,
            'codcfop' => $this->codcfop,
            'cfop' => $this->Cfop?->only(['codcfop', 'cfop', 'descricao']),
            'icmscst' => $this->icmscst !== null ? (int) $this->icmscst : null,
            'icmsbase' => $this->icmsbase !== null ? (float) $this->icmsbase : null,
            'icmspercentual' => $this->icmspercentual !== null ? (float) $this->icmspercentual : null,
            'icmslpbase' => $this->icmslpbase !== null ? (float) $this->icmslpbase : null,
            'icmslppercentual' => $this->icmslppercentual !== null ? (float) $this->icmslppercentual : null,
            'icmslppercentualimportado' => $this->icmslppercentualimportado !== null ? (float) $this->icmslppercentualimportado : null,
            'csosn' => $this->csosn,
            'ipicst' => $this->ipicst !== null ? (int) $this->ipicst : null,
            'piscst' => $this->piscst !== null ? (int) $this->piscst : null,
            'pispercentual' => $this->pispercentual !== null ? (float) $this->pispercentual : null,
            'cofinscst' => $this->cofinscst !== null ? (int) $this->cofinscst : null,
            'cofinspercentual' => $this->cofinspercentual !== null ? (float) $this->cofinspercentual : null,
            'csllpercentual' => $this->csllpercentual !== null ? (float) $this->csllpercentual : null,
            'irpjpercentual' => $this->irpjpercentual !== null ? (float) $this->irpjpercentual : null,
            'funruralpercentual' => $this->funruralpercentual !== null ? (float) $this->funruralpercentual : null,
            'senarpercentual' => $this->senarpercentual !== null ? (float) $this->senarpercentual : null,
            'fethabkg' => $this->fethabkg !== null ? (float) $this->fethabkg : null,
            'iagrokg' => $this->iagrokg !== null ? (float) $this->iagrokg : null,
            'certidaosefazmt' => $this->certidaosefazmt,
            'acumuladordominiovista' => $this->acumuladordominiovista,
            'acumuladordominioprazo' => $this->acumuladordominioprazo,
            'historicodominio' => $this->historicodominio,
            'movimentacaofisica' => $this->movimentacaofisica,
            'movimentacaocontabil' => $this->movimentacaocontabil,
            'observacoesnf' => $this->observacoesnf,
            'usuarioCriacao' => $this->relationLoaded('UsuarioCriacao') ? $this->UsuarioCriacao?->only(['codusuario', 'usuario']) : null,
            'usuarioAlteracao' => $this->relationLoaded('UsuarioAlteracao') ? $this->UsuarioAlteracao?->only(['codusuario', 'usuario']) : null,
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }
}
