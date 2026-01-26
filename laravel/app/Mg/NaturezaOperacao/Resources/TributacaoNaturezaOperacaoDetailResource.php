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

            // Tributação
            'codtributacao' => $this->codtributacao,
            'tributacao' => $this->Tributacao?->only(['codtributacao', 'tributacao']),

            // Tipo Produto
            'codtipoproduto' => $this->codtipoproduto,
            'tipoProduto' => $this->TipoProduto?->only(['codtipoproduto', 'tipoproduto']),

            // Estado
            'codestado' => $this->codestado,
            'estado' => $this->Estado?->only(['codestado', 'sigla', 'estado']),

            // NCM e BIT
            'ncm' => $this->ncm,
            'bit' => $this->bit,

            // CFOP
            'codcfop' => $this->codcfop,
            'cfop' => $this->Cfop?->only(['codcfop', 'cfop', 'descricao']),

            // ICMS
            'icmscst' => $this->icmscst !== null ? (int) $this->icmscst : null,
            'icmsbase' => $this->icmsbase !== null ? (float) $this->icmsbase : null,
            'icmspercentual' => $this->icmspercentual !== null ? (float) $this->icmspercentual : null,

            // ICMS Lucro Presumido
            'icmslpbase' => $this->icmslpbase !== null ? (float) $this->icmslpbase : null,
            'icmslppercentual' => $this->icmslppercentual !== null ? (float) $this->icmslppercentual : null,
            'icmslppercentualimportado' => $this->icmslppercentualimportado !== null ? (float) $this->icmslppercentualimportado : null,

            // Simples Nacional
            'csosn' => $this->csosn,

            // IPI
            'ipicst' => $this->ipicst !== null ? (int) $this->ipicst : null,

            // PIS
            'piscst' => $this->piscst !== null ? (int) $this->piscst : null,
            'pispercentual' => $this->pispercentual !== null ? (float) $this->pispercentual : null,

            // COFINS
            'cofinscst' => $this->cofinscst !== null ? (int) $this->cofinscst : null,
            'cofinspercentual' => $this->cofinspercentual !== null ? (float) $this->cofinspercentual : null,

            // Outros tributos
            'csllpercentual' => $this->csllpercentual !== null ? (float) $this->csllpercentual : null,
            'irpjpercentual' => $this->irpjpercentual !== null ? (float) $this->irpjpercentual : null,
            'funruralpercentual' => $this->funruralpercentual !== null ? (float) $this->funruralpercentual : null,
            'senarpercentual' => $this->senarpercentual !== null ? (float) $this->senarpercentual : null,

            // Taxas específicas MT
            'fethabkg' => $this->fethabkg !== null ? (float) $this->fethabkg : null,
            'iagrokg' => $this->iagrokg !== null ? (float) $this->iagrokg : null,
            'certidaosefazmt' => $this->certidaosefazmt,

            // Domínio contábil
            'acumuladordominiovista' => $this->acumuladordominiovista,
            'acumuladordominioprazo' => $this->acumuladordominioprazo,
            'historicodominio' => $this->historicodominio,

            // Movimentação
            'movimentacaofisica' => $this->movimentacaofisica,
            'movimentacaocontabil' => $this->movimentacaocontabil,

            // Observações
            'observacoesnf' => $this->observacoesnf,

            // Auditoria
            'usuarioCriacao' => $this->formatUsuarioCriacao(),
            'usuarioAlteracao' => $this->formatUsuarioAlteracao(),

            // Timestamps
            'criacao' => $this->criacao,
            'alteracao' => $this->alteracao,
        ];
    }

    private function formatUsuarioCriacao(): ?array
    {
        if (!$this->relationLoaded('UsuarioCriacao')) {
            return null;
        }

        return $this->UsuarioCriacao?->only(['codusuario', 'usuario']);
    }

    private function formatUsuarioAlteracao(): ?array
    {
        if (!$this->relationLoaded('UsuarioAlteracao')) {
            return null;
        }

        return $this->UsuarioAlteracao?->only(['codusuario', 'usuario']);
    }
}
